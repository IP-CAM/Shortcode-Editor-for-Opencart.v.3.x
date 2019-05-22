## Вставка шорткода в редактор Opencart 3(код  с движка Wordpress)
Подключение
1.  **catalog/model/shortcode/shortcode.php** указываем в массиве **$shortcode_tags** cвой шорткод..например ```   'productsliders' => 'productSliders' ``` ...первое это название шортокда([productsliders id="50,51"])...второе- метод в  контроллере  **catalog/controller/shortcode/shortcode.php**
2.  в  контроллере  **catalog/controller/shortcode/shortcode.php** создаем указанный метод **productSliders**, который должен возвращать нужную разметку html
3.  в нужном контроллере , например  **/catalog/controller/information/heating.php** обрабатываем текст ```                $this->load->model('shortcode/shortcode');
                                                                                                                           $data['description'] = $this->model_shortcode_shortcode->get($data['description']); ```

В редакторе ставим **[productsliders product_id="50,51"]**