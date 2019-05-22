<?php
class ControllerShortcodeShortcode extends Controller {

    /*
     * аргумент массив
     *       'attr'=>$attr,
     *       'content'=>$content,
     *       'tag'=>$tag
     */
    public function productSliders($arg){
        $this->load->model('tool/image');
        $this->load->model('catalog/product');
        extract($arg) ;
        if($attr['id']){
            $ids=explode(',',$attr['id']);
            $results = $this->model_catalog_product->getProducts($ids);
            /*
             *
             */
            $data['products']=[];
            return $this->load->view('shortcode/productsliders', $data);

        }else{
            return "";
        }
    }




}