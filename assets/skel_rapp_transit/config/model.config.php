<?php

	// Fields拡張機能設定/外部テーブルとの関連性定義
	registry("Model.extends.fields",array(
	
		// （例）Bridge関係のサンプル
		/*
		"Product.categories" =>array(
			"auto_load" =>false,
			"type" =>"assoc_bridge",
			"table" =>"ProductCategoryAssoc",
			"conditions" =>array_escape(array(
				"ProductCategoryAssoc.status" =>1,
			)),
			"parent_pk" =>"Product.id",
			"connect_by" =>"ProductCategoryAssoc.product_id",
			"reduce_by" =>"ProductCategoryAssoc.category_id",
		),
	    */
		// （例）Meta関係のサンプル
		/*
		"Product.imgs" =>array(
			"auto_load" =>false,
			"type" =>"assoc_meta",
			"table" =>"ProductMeta",
			"parent_pk" =>"Product.id",
			"connect_by" =>"ProductMeta.parent_id",
		),
		*/
	));