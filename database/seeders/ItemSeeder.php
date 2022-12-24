<?php

namespace Database\Seeders;

use App\Models\CategoryItem;
use App\Models\Item;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $item = new Item;
        $item->name = "Book 001";
        $item->category_item_id = CategoryItem::where("name", "Book")->first()->id;
        $item->save();

        $item = new Item;
        $item->name = "Book 002";
        $item->category_item_id = CategoryItem::where("name", "Book")->first()->id;
        $item->save();

        $item = new Item;
        $item->name = "Book 003";
        $item->category_item_id = CategoryItem::where("name", "Book")->first()->id;
        $item->save();
    }
}
