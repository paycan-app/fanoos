<?php

$projectRoot = dirname(__DIR__);
$examplesDir = $projectRoot . '/public/examples';
if (!is_dir($examplesDir)) {
    fwrite(STDERR, "Examples directory not found: {$examplesDir}\n");
    exit(1);
}

function pad3(int $n): string { return str_pad((string)$n, 3, '0', STR_PAD_LEFT); }
function pad4(int $n): string { return str_pad((string)$n, 4, '0', STR_PAD_LEFT); }
function dt(string $s): DateTime { return new DateTime($s); }
function fmt(DateTime $d): string { return $d->format('Y-m-d H:i:s'); }
function writeCsv(string $path, array $headers, array $rows): void {
    $f = fopen($path, 'w');

    // Explicitly set separator, enclosure, and escape to avoid deprecation warnings.
    fputcsv($f, $headers, ',', '"', '\\');

    foreach ($rows as $row) {
        fputcsv($f, $row, ',', '"', '\\');
    }

    fclose($f);
}

$toRemove = [
    $examplesDir . '/customers_example.csv',
    $examplesDir . '/products_example.csv',
    $examplesDir . '/orders_example.csv',
    $examplesDir . '/order_items_example.csv',
    $examplesDir . '/customers_example-small.csv',
    $examplesDir . '/products_example-small.csv',
    $examplesDir . '/orders_example-small.csv',
    $examplesDir . '/order_items_example-small.csv',
    $projectRoot . '/order_items_example.csv',
];
foreach ($toRemove as $file) {
    if (file_exists($file)) unlink($file);
}

$prodDefs = [
    ['Wireless Headphones','Electronics','Audio','Sonic','WH-100', 99.99,'2025-01-10 09:00:00'],
    ['Smart Watch','Electronics','Wearables','Chrono','SW-200', 149.99,'2025-01-10 09:00:00'],
    ['Bluetooth Speaker','Electronics','Audio','Boom','BS-300', 79.99,'2025-01-10 09:00:00'],
    ['USB-C Hub','Electronics','Accessories','Hubify','HB-400', 39.99,'2025-01-10 09:00:00'],
    ['Laptop Sleeve','Electronics','Accessories','Protec','LS-500', 29.99,'2025-01-10 09:00:00'],
    ['Air Fryer','Home','Kitchen','Cooko','AF-110', 89.99,'2025-01-25 09:00:00'],
    ['Vacuum Cleaner','Home','Cleaning','DustAway','VC-120', 129.99,'2025-01-25 09:00:00'],
    ['LED Desk Lamp','Home','Decor','Bright','DL-130', 24.99,'2025-01-25 09:00:00'],
    ['Memory Foam Pillow','Home','Bedding','Sleeper','FP-140', 34.99,'2025-01-25 09:00:00'],
    ['Electric Kettle','Home','Kitchen','BoilPro','EK-150', 49.99,'2025-01-25 09:00:00'],
    ['Face Serum','Beauty','Skincare','Glow','FS-210', 19.99,'2025-02-15 09:00:00'],
    ['Hair Dryer','Beauty','Hair','StyleX','HD-220', 59.99,'2025-02-15 09:00:00'],
    ['Perfume','Beauty','Fragrance','Aroma','PF-230', 79.99,'2025-02-15 09:00:00'],
    ['Lipstick','Beauty','Makeup','Colorpop','LP-240', 14.99,'2025-02-15 09:00:00'],
    ['Moisturizer','Beauty','Skincare','Hydra','MZ-250', 24.99,'2025-02-15 09:00:00'],
    ['Yoga Mat','Sports','Yoga','Flex','YM-310', 29.99,'2025-02-15 09:00:00'],
    ['Dumbbell Set','Sports','Gym','Power','DB-320', 59.99,'2025-02-15 09:00:00'],
    ['Cycling Helmet','Sports','Cycling','RideSafe','CH-330', 49.99,'2025-02-15 09:00:00'],
    ['Tennis Racket','Sports','Outdoor','Ace','TR-340', 89.99,'2025-02-15 09:00:00'],
    ['Jump Rope','Sports','Gym','Swift','JR-350', 12.99,'2025-02-15 09:00:00'],
    ['Novel: The Wanderer','Books','Fiction','BookHouse','BK-410', 14.99,'2025-02-15 09:00:00'],
    ['Study Guide','Books','Non-fiction','LearnWell','BK-420', 19.99,'2025-02-15 09:00:00'],
    ['Sci-Fi Anthology','Books','Sci-Fi','GalaxyPub','BK-430', 24.99,'2025-02-15 09:00:00'],
    ['Children Stories','Books','Children','KidsRead','BK-440', 9.99,'2025-02-15 09:00:00'],
    ['Building Blocks','Toys','Building','Blocky','TY-510', 29.99,'2025-02-15 09:00:00'],
    ['Educational Tablet','Toys','Educational','EduPlay','TY-520', 49.99,'2025-02-15 09:00:00'],
    ['Action Figure','Toys','Figures','HeroCo','TY-530', 19.99,'2025-02-15 09:00:00'],
    ['Board Game','Toys','Board Games','GameNight','TY-540', 34.99,'2025-02-15 09:00:00'],
    ['Puzzle Set','Toys','Educational','MindBend','TY-550', 14.99,'2025-02-15 09:00:00'],
    ['RC Car','Toys','Outdoor','Speedster','TY-560', 59.99,'2025-02-15 09:00:00'],
];

$products = [];
foreach ($prodDefs as $i => $def) {
    $products[] = [
        'id' => 'PROD' . pad3($i + 1),
        'title' => $def[0],
        'category' => $def[1],
        'subcategory' => $def[2],
        'brand' => $def[3],
        'sku' => $def[4],
        'price' => number_format($def[5], 2, '.', ''),
        'published_at' => $def[6],
    ];
}

$productPrice = [];
$productPublished = [];
foreach ($products as $p) {
    $productPrice[$p['id']] = (float)$p['price'];
    $productPublished[$p['id']] = dt($p['published_at']);
}

$countries = ['US','CA','UK','DE','FR','AU','BR','IN','JP','ZA'];
$channels = ['web','mobile','retail'];
$segments = ['Regular','VIP','New'];
$genders = ['male','female','other'];

$customers = [];
for ($i = 1; $i <= 50; $i++) {
    if ($i <= 10) { $base = dt('2025-01-10 09:00:00'); $base->modify('+' . ($i - 1) . ' day'); }
    elseif ($i <= 25) { $base = dt('2025-02-10 09:00:00'); $base->modify('+' . ($i - 11) . ' day'); }
    else { $base = dt('2025-03-10 09:00:00'); $base->modify('+' . max(0, $i - 26) . ' day'); if ($base->format('m') !== '03') { $base = dt('2025-03-30 09:00:00'); } }

    $birthdayDay = (($i - 1) % 28) + 1;

    $customers[] = [
        'id' => 'CUST' . pad3($i),
        'first_name' => 'First' . pad3($i),
        'last_name' => 'Last' . pad3($i),
        'email' => 'cust' . pad3($i) . '@example.com',
        'phone' => '555-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
        'country' => $countries[($i - 1) % count($countries)],
        'state' => 'State' . pad3($i),
        'city' => 'City' . pad3($i),
        'region' => 'Region' . pad3($i),
        'birthday' => sprintf('2025-05-%02d', $birthdayDay),
        'gender' => $genders[($i - 1) % count($genders)],
        'segment' => $segments[($i - 1) % count($segments)],
        'labels' => ($i % 2 === 0) ? 'newsletter,loyalty' : 'newsletter',
        'channel' => $channels[($i - 1) % count($channels)],
        'meta' => json_encode(['source' => 'demo']),
        'created_at' => fmt($base),
    ];
}

$orders = [];
$orderItems = [];
$statusCycle = ['paid','paid','shipped','paid','pending'];

for ($i = 1; $i <= 500; $i++) {
    $orderId = 'ORD' . pad4($i);
    $custIndex = (($i - 1) % 50) + 1;
    $customer = $customers[$custIndex - 1];

    // Base order date bucket
    if ($i <= 100) { $base = dt('2025-04-01 12:00:00'); $base->modify('+' . (($i - 1) % 28) . ' day'); }
    elseif ($i <= 200) { $base = dt('2025-06-01 12:00:00'); $base->modify('+' . (($i - 101) % 30) . ' day'); }
    elseif ($i <= 300) { $base = dt('2025-08-01 12:00:00'); $base->modify('+' . (($i - 201) % 31) . ' day'); }
    elseif ($i <= 400) { $base = dt('2025-10-01 12:00:00'); $base->modify('+' . (($i - 301) % 31) . ' day'); }
    else { $base = dt('2025-11-01 12:00:00'); $base->modify('+' . (($i - 401) % 30) . ' day'); }

    // Determine initial product for this order
    $prodIndex = (($i - 1) % 30) + 1;
    $firstProductId = 'PROD' . pad3($prodIndex);

    // Ensure order date after customer created_at and the product publish date
    $minStr = max($customer['created_at'], $productPublished[$firstProductId]->format('Y-m-d H:i:s'));
    $orderDt = dt($minStr);
    if ($orderDt->getTimestamp() < $base->getTimestamp()) {
        $orderDt = $base;
    }

    // Build item list for this order
    $itemsForThisOrder = [];

    // First item (always present)
    $itemsForThisOrder[] = [
        'product_id' => $firstProductId,
        'quantity' => 1,
        'unit_price' => $productPrice[$firstProductId],
    ];

    // Optional second item for 20% of orders
    if ($i % 5 === 0) {
        $secondIdx = ((($prodIndex + 7) - 1) % 30) + 1; // different product
        $secondProductId = 'PROD' . pad3($secondIdx);
        $itemsForThisOrder[] = [
            'product_id' => $secondProductId,
            'quantity' => ($i % 7 === 0) ? 2 : 1,
            'unit_price' => $productPrice[$secondProductId],
        ];
    }

    // Optional third item for 5% of orders
    if ($i % 20 === 0) {
        $thirdIdx = ((($prodIndex + 13) - 1) % 30) + 1;
        $thirdProductId = 'PROD' . pad3($thirdIdx);
        $itemsForThisOrder[] = [
            'product_id' => $thirdProductId,
            'quantity' => 1,
            'unit_price' => $productPrice[$thirdProductId],
        ];
    }

    // Compute total amount based on items
    $totalAmountFloat = 0.0;
    foreach ($itemsForThisOrder as $it) {
        $totalAmountFloat += ($it['unit_price'] * $it['quantity']);
    }
    $totalAmount = number_format($totalAmountFloat, 2, '.', '');

    $status = $statusCycle[($i - 1) % count($statusCycle)];
    $source = $channels[($i - 1) % count($channels)];

    $orders[] = [
        'id' => $orderId,
        'customer_id' => $customer['id'],
        'created_at' => fmt($orderDt),
        'total_amount' => $totalAmount,
        'status' => $status,
        'meta' => json_encode(['source' => $source]),
    ];

    // Emit order_items rows for this order
    foreach ($itemsForThisOrder as $it) {
        $orderItems[] = [
            'id' => 'ITEM' . pad4(count($orderItems) + 1), // sequential item IDs
            'order_id' => $orderId,
            'product_id' => $it['product_id'],
            'quantity' => $it['quantity'],
            'unit_price' => number_format($it['unit_price'], 2, '.', ''),
            'price' => number_format($it['unit_price'] * $it['quantity'], 2, '.', ''),
            'created_at' => fmt($orderDt),
        ];
    }
}

writeCsv(
    $examplesDir . '/products_example.csv',
    ['id','title','category','subcategory','brand','sku','price','meta'],
    array_map(function ($p) {
        return [$p['id'],$p['title'],$p['category'],$p['subcategory'],$p['brand'],$p['sku'],$p['price'], json_encode(['published_at' => $p['published_at']])];
    }, $products)
);

writeCsv(
    $examplesDir . '/customers_example.csv',
    ['id','first_name','last_name','email','phone','country','state','city','region','birthday','gender','segment','labels','channel','meta','created_at'],
    array_map(function ($c) {
        return [$c['id'],$c['first_name'],$c['last_name'],$c['email'],$c['phone'],$c['country'],$c['state'],$c['city'],$c['region'],$c['birthday'],$c['gender'],$c['segment'],$c['labels'],$c['channel'],$c['meta'],$c['created_at']];
    }, $customers)
);

writeCsv(
    $examplesDir . '/orders_example.csv',
    ['id','customer_id','created_at','total_amount','status','meta'],
    array_map(function ($o) {
        return [$o['id'],$o['customer_id'],$o['created_at'],$o['total_amount'],$o['status'],$o['meta']];
    }, $orders)
);

writeCsv(
    $examplesDir . '/order_items_example.csv',
    ['id','order_id','product_id','quantity','unit_price','price','created_at'],
    array_map(function ($oi) {
        return [$oi['id'],$oi['order_id'],$oi['product_id'],$oi['quantity'],$oi['unit_price'],$oi['price'],$oi['created_at']];
    }, $orderItems)
);

echo "Generated: customers(50), products(30), orders(" . count($orders) . "), order_items(" . count($orderItems) . ") in {$examplesDir}\n";