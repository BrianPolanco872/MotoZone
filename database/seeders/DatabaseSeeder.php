<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
        $this->call(RolePermissionSeeder::class);

        $products = [
            ['name' => 'Kit de cilindro 150 cc', 'sku' => 'MOT-CIL150', 'brand' => 'Bajaj', 'price' => 1890.00, 'stock' => 12, 'featured' => true, 'description' => 'Kit completo de cilindro con pistón, anillos y juntas para motores de 150 cc.'],
            ['name' => 'Pistón con anillos 200 cc', 'sku' => 'MOT-PIS200', 'brand' => 'Honda', 'price' => 950.00, 'stock' => 7, 'featured' => false, 'description' => 'Pistón forjado con anillos de acero para motores de 200 cc de alto rendimiento.'],
            ['name' => 'Junta de culata universal', 'sku' => 'MOT-JUN070', 'brand' => 'Genérica', 'price' => 180.00, 'stock' => 25, 'featured' => false, 'description' => 'Junta de culata resistente al calor para motores monocilíndricos de 70 a 150 cc.'],
            ['name' => 'Carburador 30 mm', 'sku' => 'MOT-CAR30', 'brand' => 'Mikuni', 'price' => 2450.00, 'stock' => 4, 'featured' => false, 'description' => 'Carburador de 30 mm con bomba de aceleración para respuesta inmediata.'],

            ['name' => 'Balatas delanteras DT175', 'sku' => 'BAL-175-F', 'brand' => 'Yamaha', 'price' => 320.00, 'stock' => 18, 'featured' => true, 'description' => 'Balatas de compuesto semimetálico con alto poder de frenado en mojado.'],
            ['name' => 'Disco de freno 240 mm', 'sku' => 'FRE-DIS240', 'brand' => 'Honda', 'price' => 780.00, 'stock' => 9, 'featured' => false, 'description' => 'Disco flotante de acero inoxidable de 240 mm para frenos hidráulicos.'],
            ['name' => 'Kit de balatas traseras universal', 'sku' => 'BAL-UNI-T', 'brand' => 'Genérica', 'price' => 260.00, 'stock' => 30, 'featured' => false, 'description' => 'Par de balatas traseras para tambor de 110 mm compatible con múltiples modelos.'],
            ['name' => 'Líquido de frenos DOT 4 · 500 ml', 'sku' => 'FRE-DOT4', 'brand' => 'Bosch', 'price' => 145.00, 'stock' => 3, 'featured' => false, 'description' => 'Líquido de frenos sintético DOT 4 con punto de ebullición de 230 °C.'],

            ['name' => 'Llanta 90/90-18 TR', 'sku' => 'LLA-9090-18', 'brand' => 'Kenda', 'price' => 1250.00, 'stock' => 14, 'featured' => true, 'description' => 'Llanta delantera con dibujo mixto para asfalto y caminos de tierra.'],
            ['name' => 'Llanta 110/90-17 trasera', 'sku' => 'LLA-11090-17', 'brand' => 'Michelin', 'price' => 1890.00, 'stock' => 6, 'featured' => false, 'description' => 'Compuesto de larga duración con excelente agarre en curvas.'],
            ['name' => 'Cámara 2.75-18', 'sku' => 'CAM-275-18', 'brand' => 'IRC', 'price' => 210.00, 'stock' => 40, 'featured' => false, 'description' => 'Cámara de butilo reforzada para llantas de 18 pulgadas.'],
            ['name' => 'Rin deportivo 17 pulgadas', 'sku' => 'RIN-17-DEP', 'brand' => 'Excel', 'price' => 2300.00, 'stock' => 2, 'featured' => false, 'description' => 'Rin de aluminio de 17 pulgadas con acabado anodizado negro mate.'],

            ['name' => 'Kit de arrastre 428H', 'sku' => 'KIT-428H', 'brand' => 'DID', 'price' => 890.00, 'stock' => 2, 'featured' => true, 'description' => 'Kit completo con cadena 428H, piñón y catalina para motos de 125 a 150 cc.'],
            ['name' => 'Cadena 520 X-ring 120L', 'sku' => 'CAD-520-120', 'brand' => 'RK', 'price' => 1750.00, 'stock' => 10, 'featured' => false, 'description' => 'Cadena sellada con retenes X-ring de 120 eslabones para motos de mayor cilindrada.'],
            ['name' => 'Piñón 14 dientes', 'sku' => 'PIN-14T', 'brand' => 'JT Sprockets', 'price' => 240.00, 'stock' => 22, 'featured' => false, 'description' => 'Piñón de acero templado de 14 dientes con estriado estándar.'],
            ['name' => 'Catalina 42 dientes', 'sku' => 'CAT-42T', 'brand' => 'JT Sprockets', 'price' => 480.00, 'stock' => 15, 'featured' => false, 'description' => 'Catalina de acero de 42 dientes para relaciones de arrastre mixtas.'],

            ['name' => 'Bujía NGK CR7HSA', 'sku' => 'BUJ-CR7', 'brand' => 'NGK', 'price' => 95.00, 'stock' => 5, 'featured' => true, 'description' => 'Bujía de níquel con electrodo de cobre para encendido estable.'],
            ['name' => 'Batería 12V 5Ah', 'sku' => 'BAT-12V5', 'brand' => 'Yuasa', 'price' => 1150.00, 'stock' => 11, 'featured' => true, 'description' => 'Batería sellada libre de mantenimiento con alta capacidad de arranque.'],
            ['name' => 'Bobina de alta 150 cc', 'sku' => 'BOB-150', 'brand' => 'Honda', 'price' => 420.00, 'stock' => 8, 'featured' => false, 'description' => 'Bobina de encendido original para motores de 150 cc.'],
            ['name' => 'Faro LED universal 7"', 'sku' => 'FAR-LED7', 'brand' => 'Genérica', 'price' => 980.00, 'stock' => 4, 'featured' => false, 'description' => 'Faro LED de 7 pulgadas con luz baja, alta y luz de posición.'],

            ['name' => 'Amortiguadores traseros 340 mm', 'sku' => 'AMO-340', 'brand' => 'YSS', 'price' => 2150.00, 'stock' => 6, 'featured' => true, 'description' => 'Par de amortiguadores con precarga ajustable y cuerpo de gas.'],
            ['name' => 'Horquilla delantera completa', 'sku' => 'HOR-150', 'brand' => 'Honda', 'price' => 3400.00, 'stock' => 3, 'featured' => false, 'description' => 'Horquilla delantera completa con barras de 33 mm y sellos nuevos.'],
            ['name' => 'Retenes de horquilla 33 mm', 'sku' => 'RET-33', 'brand' => 'All Balls', 'price' => 280.00, 'stock' => 20, 'featured' => false, 'description' => 'Juego de retenes y guardapolvos para barras de suspensión de 33 mm.'],

            ['name' => 'Casco integral talla M', 'sku' => 'CAS-INT-M', 'brand' => 'LS2', 'price' => 2890.00, 'stock' => 9, 'featured' => true, 'description' => 'Casco integral con visor antiempañante y certificación DOT.'],
            ['name' => 'Guantes de moto táctiles', 'sku' => 'GUA-TAC-L', 'brand' => 'Alpinestars', 'price' => 1250.00, 'stock' => 12, 'featured' => false, 'description' => 'Guantes con protección en nudillos y dedos compatibles con pantalla táctil.'],
            ['name' => 'Espejos retrovisores universales', 'sku' => 'ESP-UNI', 'brand' => 'Genérica', 'price' => 350.00, 'stock' => 26, 'featured' => false, 'description' => 'Par de espejos con rosca universal de 10 mm y brazo ajustable.'],
            ['name' => 'Cargador USB para manubrio', 'sku' => 'CAR-USB', 'brand' => 'Genérica', 'price' => 290.00, 'stock' => 5, 'featured' => false, 'description' => 'Cargador impermeable de 2.1 A con soporte para manubrio.'],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'name' => $product['name'],
                    'slug' => str($product['name'])->slug()->value(),
                    'brand' => $product['brand'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'cost' => round($product['price'] * 0.65, 2),
                    'min_stock' => 3,
                    'track_inventory' => true,
                    'stock' => $product['stock'],
                    'featured' => $product['featured'],
                    'is_active' => true,
                ],
            );
        }
    }
}
