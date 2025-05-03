<?php

/*
Title: Order Information
Post Type: tiktok_order
Order: 2
*/

piklist('field', ['type' => 'text', 'field' => 'order_number', 'label' => 'Order Number', 'columns' => 6]);
piklist('field', ['type' => 'text', 'field' => 'shop_code', 'label' => 'Shop Code', 'columns' => 6]);
piklist('field', ['type' => 'textarea', 'field' => 'order_notice', 'label' => 'Order Notice', 'columns' => 6]);
piklist('field', ['type' => 'text', 'field' => 'customer_name', 'label' => 'Customer Email', 'columns' => 6]);

piklist('field', [
    'type' => 'group',
    'field' => 'order_items',
    'label' => 'Order Items',
    'add_more' => true,
    'fields' => [
        [
            'type' => 'url',
            'field' => 'image',
            'label' => 'Product Image',
            'columns' => 12,
        ],
        [
            'type' => 'text',
            'field' => 'product_name',
            'columns' => 6,
            'label' => 'Product Name'
        ],
        [
            'type' => 'text',
            'columns' => 6,
            'field' => 'sku',
            'label' => 'Product SKU'
        ],
        [
            'type' => 'number',
            'columns' => 6,
            'field' => 'quantity',
            'label' => 'Quantity'
        ],
        [
            'type' => 'number',
            'field' => 'price',
            'columns' => 6,
            'label' => 'Price (VNĐ)'
        ],
    ]
]);


piklist('field', ['type' => 'number', 'field' => 'total', 'label' => 'Total Price (VNĐ)', 'columns' => 6]);
piklist('field', ['type' => 'number', 'field' => 'net_revenue', 'label' => 'Net Revenue (VNĐ)', 'columns' => 6]);

