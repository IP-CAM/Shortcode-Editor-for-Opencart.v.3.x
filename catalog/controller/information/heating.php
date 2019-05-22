<?php
class ControllerInformationHeating extends Controller {
    public function index() {

        $this->load->language('information/heating');

        $this->load->model('catalog/heating');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        if (isset($this->request->get['information_id'])) {
            $information_id = (int)$this->request->get['information_id'];
        } else {
            $information_id = 0;
        }
        $information_info = $this->model_catalog_heating->getInformation($information_id);
        if ($information_info) {
            $this->document->setTitle($information_info['meta_title']);
            $this->document->setDescription($information_info['meta_description']);
            $this->document->setKeywords($information_info['meta_keyword']);

            $data['breadcrumbs'][] = array(
                'text' => $information_info['title'],
                'href' => $this->url->link('information/heating', 'information_id=' .  $information_id)
            );

            $data['heading_title'] = $information_info['title'];

            $data['description'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');
            //*********************************************************************
            //шорткод
//            if($data['description']){
                $this->load->model('shortcode/shortcode');
                $data['description'] = $this->model_shortcode_shortcode->get($data['description']);
//            }


            $data['continue'] = $this->url->link('common/home');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');



            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');


            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "related_product` WHERE information_id = '" . (int)$this->request->get['information_id'] . "' ");
            $data['related_product']=false;
            $data['products'] = array();

            if($query->num_rows&&$query->row){
                $data['related_product']=$query->row;
                if(!empty($data['related_product']["product"])){
                    $products=explode(',',$data['related_product']["product"]);
                    $this->load->model('catalog/category');
                    $this->load->model('catalog/product');
                    $this->load->model('tool/image');
                    $url="";

                    foreach ($products as $product_id){
                        $result=$this->model_catalog_product->getProduct($product_id);

                        if ($result['image']) {
                            $image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
                        } else {
                            $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
                        }



                        //images******************
                        $result_images = $this->model_catalog_product->getProductImages($result['product_id']);
                        $images=[];
                        foreach ($result_images as $result_image) {
                            $images[] = array(
                                'image' => $this->model_tool_image->resize(
                                    $result_image['image'],
                                    $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'),
                                    $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')),
                                'color'=>$result_image['color']
                            );
                        }
                        //images****************** end


                        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                            $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                        } else {
                            $price = false;
                        }

                        if ((float)$result['special']) {
                            $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                        } else {
                            $special = false;
                        }

                        if ($this->config->get('config_tax')) {
                            $tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
                        } else {
                            $tax = false;
                        }

                        if ($this->config->get('config_review_status')) {
                            $rating = (int)$result['rating'];
                        } else {
                            $rating = false;
                        }
                        $attributeCart=[];
                        $attributePopup=[];
                        $attribute_groups = $this->model_catalog_product->getProductAttributes($result['product_id']);
                        foreach ($attribute_groups as $attribute_group){
                            if($attribute_group["attribute_group_id"]==7){
                                $attributeCart=$attribute_group ["attribute"];
                            }
                            if($attribute_group["attribute_group_id"]==8){
                                $attributePopup=$attribute_group ["attribute"];
                            }
                        }


                        $data['products'][] = array(
                            'product_id'  => $result['product_id'],
                            'thumb'       => $image,
                            'color'       => $result['color'],
                            'images'      =>$images,
                            'name'        => $result['name'],
                            'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                            'price'       => $price,
                            'special'     => $special,
                            'tax'         => $tax,
                            'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
                            'rating'      => $result['rating'],
                            'href'        => $this->url->link('product/product',  '&product_id=' . $result['product_id'] . $url),
                            'attributeCart'=> $attributeCart,
                            'attributePopup'=>$attributePopup,

                        );


                    }

                }
            }
            $this->response->setOutput($this->load->view('information/heating', $data));
        }
        else {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_error'),
                'href' => $this->url->link('information/information', 'information_id=' . $information_id)
            );

            $this->document->setTitle($this->language->get('text_error'));

            $data['heading_title'] = $this->language->get('text_error');

            $data['text_error'] = $this->language->get('text_error');

            $data['continue'] = $this->url->link('common/home');

            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    public function agree() {
        $this->load->model('catalog/information');

        if (isset($this->request->get['information_id'])) {
            $information_id = (int)$this->request->get['information_id'];
        } else {
            $information_id = 0;
        }

        $output = '';

        $information_info = $this->model_catalog_information->getInformation($information_id);

        if ($information_info) {
            $output .= html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8') . "\n";
        }

        $this->response->setOutput($output);
    }

    public function ajaxGetProduct(){
        $information_id=$_POST['information_id'];
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "related_product` WHERE information_id = '" . (int)$information_id . "' ");
        if ($query->rows) {
            $this->db->query("UPDATE " . DB_PREFIX . "related_product SET title = '" . $this->db->escape($_POST['title']) . "', product = '". $_POST['product']. "'  WHERE information_id = '" . (int)$information_id . "'");
        }else{
            $this->db->query("INSERT INTO " . DB_PREFIX . "related_product SET information_id = '" . (int)$information_id . "', `title` = '" . $_POST['title'] . "', `product` = '" . $_POST['product'] . "'");
        }

        echo json_encode(['suc'=>true]);


    }
}