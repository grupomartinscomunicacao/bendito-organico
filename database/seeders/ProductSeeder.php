<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProductUnit;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A realistic starting catalog: what a small organic grower actually sells.
 *
 * Seeded with updateOrCreate on the slug so re-running the seeder refreshes
 * the demo data instead of duplicating it.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->products() as $index => $product) {
            $slug = Str::slug($product['name']);

            Product::updateOrCreate(
                ['slug' => $slug],
                $product + [
                    'slug' => $slug,
                    'sort_order' => $index,
                    'track_stock' => true,
                    'is_active' => true,
                    'image' => "images/products/{$slug}.png",
                    'meta_title' => "{$product['name']} orgânica — ".config('bendito.name'),
                    'meta_description' => $product['short_description'],
                ],
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function products(): array
    {
        return [
            [
                'name' => 'Alface Crespa',
                'short_description' => 'Folhas macias e crocantes, colhidas na manhã da entrega.',
                'description' => "A alface crespa é a base de qualquer salada fresca. As nossas crescem em canteiros ao ar livre, sem nenhum agrotóxico, e são colhidas na mesma manhã em que saem para entrega — por isso chegam com as folhas firmes e o talo ainda úmido.\n\nGuarde na geladeira dentro de um saco perfurado, sem lavar. Dura de 5 a 7 dias.",
                'price' => 5.90,
                'unit' => ProductUnit::Unit,
                'stock' => 48,
                'is_featured' => true,
            ],
            [
                'name' => 'Rúcula',
                'short_description' => 'Sabor marcante e levemente picante, folha jovem.',
                'description' => "Rúcula de folha jovem, com aquele amargor elegante que dá personalidade à salada. Combina com pera, castanhas e um bom azeite.\n\nColhida em maços fechados no dia anterior à entrega.",
                'price' => 4.50,
                'unit' => ProductUnit::Bunch,
                'stock' => 36,
                'is_featured' => true,
            ],
            [
                'name' => 'Couve Manteiga',
                'short_description' => 'Folha larga e tenra, perfeita para refogar.',
                'description' => "A couve manteiga é a mais macia das couves e a favorita para acompanhar feijoada ou virar um refogado rápido com alho.\n\nVendida em maços de aproximadamente 10 folhas.",
                'price' => 4.90,
                'unit' => ProductUnit::Bunch,
                'stock' => 40,
            ],
            [
                'name' => 'Tomate Italiano',
                'short_description' => 'Polpa densa e pouca semente — o tomate do molho.',
                'description' => "Tomate italiano maduro no pé, com polpa densa e acidez equilibrada. É o tomate certo para molho, mas também vai muito bem cru, fatiado com azeite e sal grosso.\n\nAmadurecido naturalmente, sem câmara de maturação.",
                'price' => 12.90,
                'compare_at_price' => 15.90,
                'unit' => ProductUnit::Kilogram,
                'stock' => 25,
                'is_featured' => true,
            ],
            [
                'name' => 'Cenoura',
                'short_description' => 'Doce, crocante e colhida pequena.',
                'description' => "Cenouras colhidas antes de atingirem o tamanho máximo — ficam mais doces e sem aquele miolo fibroso. Ótimas cruas, no vapor ou assadas inteiras.\n\nVêm com um pouco de terra: é assim que se conservam melhor.",
                'price' => 8.50,
                'unit' => ProductUnit::Kilogram,
                'stock' => 30,
            ],
            [
                'name' => 'Beterraba',
                'short_description' => 'Doçura profunda, ótima assada ou crua ralada.',
                'description' => "Beterraba de casca fina e cor intensa. Assada inteira no papel-alumínio ela concentra o açúcar e fica quase caramelizada.\n\nAs folhas também são comestíveis — refogue como espinafre.",
                'price' => 7.90,
                'unit' => ProductUnit::Kilogram,
                'stock' => 22,
            ],
            [
                'name' => 'Brócolis Ninja',
                'short_description' => 'Cabeça compacta, talo tenro, sem desperdício.',
                'description' => "O brócolis ninja tem a cabeça mais compacta e o talo bem mais tenro que o comum — dá para usar a planta inteira.\n\nCozinhe no vapor por 4 minutos para manter o verde vivo e a crocância.",
                'price' => 9.90,
                'unit' => ProductUnit::Unit,
                'stock' => 18,
                'is_featured' => true,
            ],
            [
                'name' => 'Abobrinha Italiana',
                'short_description' => 'Casca fina e macia, colhida pequena.',
                'description' => "Abobrinhas colhidas ainda pequenas, quando a casca é fina e a semente quase não existe. Fatiada em tiras finas vira um ótimo substituto para a massa.\n\nNão precisa descascar.",
                'price' => 7.50,
                'unit' => ProductUnit::Kilogram,
                'stock' => 26,
            ],
            [
                'name' => 'Pepino Japonês',
                'short_description' => 'Crocante, sem amargor e com casca comestível.',
                'description' => "O pepino japonês é mais fino, mais crocante e não amarga. A casca é fina o bastante para comer junto, o que preserva boa parte das fibras.\n\nExcelente em conservas rápidas com vinagre de arroz.",
                'price' => 6.90,
                'unit' => ProductUnit::Kilogram,
                'stock' => 20,
            ],
            [
                'name' => 'Cheiro-verde',
                'short_description' => 'Salsinha e cebolinha no mesmo maço.',
                'description' => "Salsinha e cebolinha amarradas juntas, como se faz na feira. Colhidas na véspera para chegarem firmes.\n\nConserve em um copo com dois dedos de água na geladeira — dura o dobro.",
                'price' => 3.90,
                'unit' => ProductUnit::Bunch,
                'stock' => 55,
            ],
            [
                'name' => 'Manjericão',
                'short_description' => 'Aroma intenso, folha grande — pesto na certa.',
                'description' => "Manjericão de folha larga e aroma intenso, cortado no dia. Um maço rende um bom pote de pesto.\n\nNunca guarde na geladeira: o frio escurece as folhas. Deixe o talo na água, fora do sol.",
                'price' => 4.90,
                'unit' => ProductUnit::Bunch,
                'stock' => 24,
            ],
            [
                'name' => 'Mix de Folhas',
                'short_description' => 'Alface, rúcula, agrião e mizuna em bandeja.',
                'description' => "Uma bandeja com quatro folhas diferentes já higienizadas e prontas para servir: alface crespa, rúcula, agrião e mizuna.\n\nÉ a opção prática de quem quer salada boa sem preparo — abra e sirva.",
                'price' => 16.90,
                'compare_at_price' => 19.90,
                'unit' => ProductUnit::Tray,
                'stock' => 12,
                'is_featured' => true,
            ],
            [
                'name' => 'Batata Doce',
                'short_description' => 'Polpa alaranjada, doce e cremosa.',
                'description' => "Batata doce de polpa alaranjada, mais úmida e mais doce que a roxa. Assada inteira a 200 °C por 40 minutos fica cremosa por dentro.\n\nColhida e curada por uma semana antes da venda — é o que garante a doçura.",
                'price' => 6.50,
                'unit' => ProductUnit::Kilogram,
                'stock' => 34,
            ],
            [
                'name' => 'Espinafre',
                'short_description' => 'Folha tenra, sem talo duro.',
                'description' => "Espinafre de folha jovem e talo fino, que não precisa ser removido. Murcha em segundos na frigideira quente com um fio de azeite.\n\nRende muito menos do que parece: um maço vira duas porções.",
                'price' => 5.50,
                'unit' => ProductUnit::Bunch,
                'stock' => 0,
            ],
        ];
    }
}
