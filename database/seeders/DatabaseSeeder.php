<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
        $this->call(RolePermissionSeeder::class);

        $categories = [
            ['name' => 'Motor y partes', 'slug' => 'motor', 'description' => 'Cilindros, pistones, carburadores y todo lo que mueve tu motor.', 'sort_order' => 1],
            ['name' => 'Frenos', 'slug' => 'frenos', 'description' => 'Balatas, discos y líquido para detenerte con seguridad.', 'sort_order' => 2],
            ['name' => 'Llantas y ruedas', 'slug' => 'llantas', 'description' => 'Llantas, cámaras y rines para cualquier tipo de terreno.', 'sort_order' => 3],
            ['name' => 'Transmisión', 'slug' => 'transmision', 'description' => 'Kits de arrastre, cadenas, piñones y catalinas.', 'sort_order' => 4],
            ['name' => 'Eléctrico', 'slug' => 'electrico', 'description' => 'Bujías, baterías, bobinas e iluminación LED.', 'sort_order' => 5],
            ['name' => 'Suspensión', 'slug' => 'suspension', 'description' => 'Amortiguadores, horquillas y retenes de dirección.', 'sort_order' => 6],
            ['name' => 'Accesorios', 'slug' => 'accesorios', 'description' => 'Cascos, guantes y equipo para rodar cómodo y protegido.', 'sort_order' => 7],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }

        $categories = Category::pluck('id', 'slug');

        $products = [
            ['category' => 'motor', 'name' => 'Kit de cilindro 150 cc', 'sku' => 'MOT-CIL150', 'brand' => 'Bajaj', 'price' => 1890.00, 'stock' => 12, 'featured' => true, 'description' => 'Kit completo de cilindro con pistón, anillos y juntas para motores de 150 cc.'],
            ['category' => 'motor', 'name' => 'Pistón con anillos 200 cc', 'sku' => 'MOT-PIS200', 'brand' => 'Honda', 'price' => 950.00, 'stock' => 7, 'featured' => false, 'description' => 'Pistón forjado con anillos de acero para motores de 200 cc de alto rendimiento.'],
            ['category' => 'motor', 'name' => 'Junta de culata universal', 'sku' => 'MOT-JUN070', 'brand' => 'Genérica', 'price' => 180.00, 'stock' => 25, 'featured' => false, 'description' => 'Junta de culata resistente al calor para motores monocilíndricos de 70 a 150 cc.'],
            ['category' => 'motor', 'name' => 'Carburador 30 mm', 'sku' => 'MOT-CAR30', 'brand' => 'Mikuni', 'price' => 2450.00, 'stock' => 4, 'featured' => false, 'description' => 'Carburador de 30 mm con bomba de aceleración para respuesta inmediata.'],

            ['category' => 'frenos', 'name' => 'Balatas delanteras DT175', 'sku' => 'BAL-175-F', 'brand' => 'Yamaha', 'price' => 320.00, 'stock' => 18, 'featured' => true, 'description' => 'Balatas de compuesto semimetálico con alto poder de frenado en mojado.'],
            ['category' => 'frenos', 'name' => 'Disco de freno 240 mm', 'sku' => 'FRE-DIS240', 'brand' => 'Honda', 'price' => 780.00, 'stock' => 9, 'featured' => false, 'description' => 'Disco flotante de acero inoxidable de 240 mm para frenos hidráulicos.'],
            ['category' => 'frenos', 'name' => 'Kit de balatas traseras universal', 'sku' => 'BAL-UNI-T', 'brand' => 'Genérica', 'price' => 260.00, 'stock' => 30, 'featured' => false, 'description' => 'Par de balatas traseras para tambor de 110 mm compatible con múltiples modelos.'],
            ['category' => 'frenos', 'name' => 'Líquido de frenos DOT 4 · 500 ml', 'sku' => 'FRE-DOT4', 'brand' => 'Bosch', 'price' => 145.00, 'stock' => 3, 'featured' => false, 'description' => 'Líquido de frenos sintético DOT 4 con punto de ebullición de 230 °C.'],

            ['category' => 'llantas', 'name' => 'Llanta 90/90-18 TR', 'sku' => 'LLA-9090-18', 'brand' => 'Kenda', 'price' => 1250.00, 'stock' => 14, 'featured' => true, 'description' => 'Llanta delantera con dibujo mixto para asfalto y caminos de tierra.'],
            ['category' => 'llantas', 'name' => 'Llanta 110/90-17 trasera', 'sku' => 'LLA-11090-17', 'brand' => 'Michelin', 'price' => 1890.00, 'stock' => 6, 'featured' => false, 'description' => 'Compuesto de larga duración con excelente agarre en curvas.'],
            ['category' => 'llantas', 'name' => 'Cámara 2.75-18', 'sku' => 'CAM-275-18', 'brand' => 'IRC', 'price' => 210.00, 'stock' => 40, 'featured' => false, 'description' => 'Cámara de butilo reforzada para llantas de 18 pulgadas.'],
            ['category' => 'llantas', 'name' => 'Rin deportivo 17 pulgadas', 'sku' => 'RIN-17-DEP', 'brand' => 'Excel', 'price' => 2300.00, 'stock' => 2, 'featured' => false, 'description' => 'Rin de aluminio de 17 pulgadas con acabado anodizado negro mate.'],

            ['category' => 'transmision', 'name' => 'Kit de arrastre 428H', 'sku' => 'KIT-428H', 'brand' => 'DID', 'price' => 890.00, 'stock' => 2, 'featured' => true, 'description' => 'Kit completo con cadena 428H, piñón y catalina para motos de 125 a 150 cc.'],
            ['category' => 'transmision', 'name' => 'Cadena 520 X-ring 120L', 'sku' => 'CAD-520-120', 'brand' => 'RK', 'price' => 1750.00, 'stock' => 10, 'featured' => false, 'description' => 'Cadena sellada con retenes X-ring de 120 eslabones para motos de mayor cilindrada.'],
            ['category' => 'transmision', 'name' => 'Piñón 14 dientes', 'sku' => 'PIN-14T', 'brand' => 'JT Sprockets', 'price' => 240.00, 'stock' => 22, 'featured' => false, 'description' => 'Piñón de acero templado de 14 dientes con estriado estándar.'],
            ['category' => 'transmision', 'name' => 'Catalina 42 dientes', 'sku' => 'CAT-42T', 'brand' => 'JT Sprockets', 'price' => 480.00, 'stock' => 15, 'featured' => false, 'description' => 'Catalina de acero de 42 dientes para relaciones de arrastre mixtas.'],

            ['category' => 'electrico', 'name' => 'Bujía NGK CR7HSA', 'sku' => 'BUJ-CR7', 'brand' => 'NGK', 'price' => 95.00, 'stock' => 5, 'featured' => true, 'description' => 'Bujía de níquel con electrodo de cobre para encendido estable.'],
            ['category' => 'electrico', 'name' => 'Batería 12V 5Ah', 'sku' => 'BAT-12V5', 'brand' => 'Yuasa', 'price' => 1150.00, 'stock' => 11, 'featured' => true, 'description' => 'Batería sellada libre de mantenimiento con alta capacidad de arranque.'],
            ['category' => 'electrico', 'name' => 'Bobina de alta 150 cc', 'sku' => 'BOB-150', 'brand' => 'Honda', 'price' => 420.00, 'stock' => 8, 'featured' => false, 'description' => 'Bobina de encendido original para motores de 150 cc.'],
            ['category' => 'electrico', 'name' => 'Faro LED universal 7"', 'sku' => 'FAR-LED7', 'brand' => 'Genérica', 'price' => 980.00, 'stock' => 4, 'featured' => false, 'description' => 'Faro LED de 7 pulgadas con luz baja, alta y luz de posición.'],

            ['category' => 'suspension', 'name' => 'Amortiguadores traseros 340 mm', 'sku' => 'AMO-340', 'brand' => 'YSS', 'price' => 2150.00, 'stock' => 6, 'featured' => true, 'description' => 'Par de amortiguadores con precarga ajustable y cuerpo de gas.'],
            ['category' => 'suspension', 'name' => 'Horquilla delantera completa', 'sku' => 'HOR-150', 'brand' => 'Honda', 'price' => 3400.00, 'stock' => 3, 'featured' => false, 'description' => 'Horquilla delantera completa con barras de 33 mm y sellos nuevos.'],
            ['category' => 'suspension', 'name' => 'Retenes de horquilla 33 mm', 'sku' => 'RET-33', 'brand' => 'All Balls', 'price' => 280.00, 'stock' => 20, 'featured' => false, 'description' => 'Juego de retenes y guardapolvos para barras de suspensión de 33 mm.'],

            ['category' => 'accesorios', 'name' => 'Casco integral talla M', 'sku' => 'CAS-INT-M', 'brand' => 'LS2', 'price' => 2890.00, 'stock' => 9, 'featured' => true, 'description' => 'Casco integral con visor antiempañante y certificación DOT.'],
            ['category' => 'accesorios', 'name' => 'Guantes de moto táctiles', 'sku' => 'GUA-TAC-L', 'brand' => 'Alpinestars', 'price' => 1250.00, 'stock' => 12, 'featured' => false, 'description' => 'Guantes con protección en nudillos y dedos compatibles con pantalla táctil.'],
            ['category' => 'accesorios', 'name' => 'Espejos retrovisores universales', 'sku' => 'ESP-UNI', 'brand' => 'Genérica', 'price' => 350.00, 'stock' => 26, 'featured' => false, 'description' => 'Par de espejos con rosca universal de 10 mm y brazo ajustable.'],
            ['category' => 'accesorios', 'name' => 'Cargador USB para manubrio', 'sku' => 'CAR-USB', 'brand' => 'Genérica', 'price' => 290.00, 'stock' => 5, 'featured' => false, 'description' => 'Cargador impermeable de 2.1 A con soporte para manubrio.'],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'category_id' => $categories[$product['category']],
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
