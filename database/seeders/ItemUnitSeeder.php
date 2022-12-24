<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $item_unit1 = new ItemUnit;
        $item_unit1->unit_id = Unit::where("name", "pcs")->first()->id;
        $item_unit1->item_id = Item::where("name", "Book 001")->first()->id;
        $item_unit1->multiplier = 1;
        $item_unit1->parent_id = null;
        $item_unit1->save();

        $item_unit = new ItemUnit;
        $item_unit->unit_id = Unit::where("name", "doz")->first()->id;
        $item_unit->item_id = Item::where("name", "Book 001")->first()->id;
        $item_unit->multiplier = 20;
        $item_unit->parent_id = $item_unit1->id;
        $item_unit->save();

        $item_unit1 = new ItemUnit;
        $item_unit1->unit_id = Unit::where("name", "bal")->first()->id;
        $item_unit1->item_id = Item::where("name", "Book 002")->first()->id;
        $item_unit1->multiplier = 1;
        $item_unit1->parent_id = null;
        $item_unit1->save();

        $item_unit = new ItemUnit;
        $item_unit->unit_id = Unit::where("name", "box")->first()->id;
        $item_unit->item_id = Item::where("name", "Book 002")->first()->id;
        $item_unit->multiplier = 15;
        $item_unit->parent_id = $item_unit1->id;
        $item_unit->save();

    }
}
