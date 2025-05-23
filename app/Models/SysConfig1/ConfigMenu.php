<?php

namespace App\Models\SysConfig1;
use App\Helpers\SequenceUtility;
use App\Models\SysConfig1\Base\SysConfig1BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigMenu extends SysConfig1BaseModel
{
    protected $table = 'config_menus';
    use SoftDeletes;

    public static function boot()
    {
        parent::boot();
        // static::creating(function ($model) {
        //     $maxId = SequenceUtility::getCurrentSequenceValue($model);
        //     $model->code = 'MENU' ."_". ($maxId + 1);
        // });
    }

    protected $fillable = ['code', 'app_id', 'app_code', 'menu_header', 'menu_caption', 'menu_link'];

    #region Relations

    public function ConfigAppl()
    {
        return $this->belongsTo(ConfigAppl::class, 'app_id', 'id');
    }

    #endregion

    #region Attributes
    #endregion
    public function scopeGetActiveData()
    {
        return $this->orderBy('menu_header', 'asc')->orderBy('seq', 'asc')->get();
    }

    public static function getMenuNameByLink($menuLink)
    {
        $menu = self::where('menu_link', $menuLink)->first();
        if ($menu) {
            return $menu->menu_caption;
        } else {
            return '';
        }
    }

    /**
     * Get the render route based on the current route name.
     *
     * @param string $routeName
     * @return string
     */
    public static function getRoute($routeName)
    {
        // Convert camelCase to kebab-case for each segment of the route name
        $renderRoute = implode(
            '.',
            array_map(function ($segment) {
                return preg_replace_callback(
                    '/(?<=\w)([A-Z])/',
                    function ($match) use ($segment) {
                        $prevChar = substr($segment, strpos($segment, $match[0]) - 1, 1);
                        if ($prevChar === '_') {
                            return $match[0];
                        } else {
                            return '-' . strtolower($match[1]);
                        }
                    },
                    $segment,
                );
            }, explode('.', $routeName)),
        );

        // Convert the entire route to lowercase except the first character of each segment
        $renderRoute = implode(
            '.',
            array_map(function ($segment) {
                return lcfirst($segment);
            }, explode('.', $renderRoute)),
        );

        // Convert the entire route to lowercase
        $baseRenderRoute = strtolower($renderRoute);

        return $baseRenderRoute;
    }

    //    /**
    //  * Get the full path based on request segments, action value, and additional parameters.
    //  *
    //  * @param array $segments
    //  * @param string|null $actionValue
    //  * @param string|null $additionalParam
    //  * @return string
    //  */
    // public static function getFullPathLink(array $segments, $actionValue = null, $additionalParam = null)
    // {
    //     $segmentsToIgnore = 0;

    //     if (in_array($actionValue, ['Edit', 'View'])) {
    //         $segmentsToIgnore = 3;
    //     } elseif ($actionValue == 'Create') {
    //         $segmentsToIgnore = 2;
    //     }

    //     if ($additionalParam) {
    //         $additionalSegments = count(explode('/', $additionalParam));
    //         $segmentsToIgnore += $additionalSegments;
    //     }

    //     if ($segmentsToIgnore > 0 && count($segments) > $segmentsToIgnore) {
    //         $segments = array_slice($segments, 0, -$segmentsToIgnore);
    //     }

    //     if (!empty($segments)) {
    //         $lastSegmentIndex = count($segments) - 1;
    //         if (strpos($segments[$lastSegmentIndex], 'Detail') !== false) {
    //             array_pop($segments);
    //         }
    //     }

    //     return implode('/', $segments);
    // }

  /**
 * Get the full path based on request segments, action value, and additional parameters,
 * but remove any "table-filter" query parameter.
 *
 * @param string      $menuLink        e.g. "TrdRetail1/Master/Partner?TYPE=C&foo=bar&table-filter=xyz"
 * @param string|null $actionValue     e.g. "Edit"
 * @param string|null $additionalParam not used here
 * @return string                      e.g. "TrdRetail1/Master/Partner?TYPE=C&foo=bar"
 */
public static function getFullPathLink($menuLink, $actionValue = null, $additionalParam = null)
{
    // 1) Split off the path and query string
    [$path, $query] = array_pad(explode('?', $menuLink, 2), 2, '');

    // 2) Pop the last path segment for Create/Edit/View actions
    $segments = explode('/', $path);
    if (in_array($actionValue, ['Create', 'Edit', 'View'], true) && count($segments) > 0) {
        array_pop($segments);
    }
    $newPath = implode('/', $segments);

    // 3) If there was a query string, parse it
    if ($query !== '') {
        parse_str($query, $allParams);

        // 4) Build a filtered array without "table-filter"
        $filteredParams = array_filter($allParams, function($value, $key) {
            return !in_array($key, ['table-filters', 'page'], true);
        }, ARRAY_FILTER_USE_BOTH);

        // 5) Re‑append the remaining params, if any
        if (! empty($filteredParams)) {
            return $newPath . '?' . http_build_query($filteredParams);
        }
    }

    return $newPath;
}

}
