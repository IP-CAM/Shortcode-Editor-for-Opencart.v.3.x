## Inserting a shortcode into the Opencart 3 editor (code from the Wordpress engine)
Connection
1. ** catalog / model / shortcode / shortcode.php ** specify in the array ** $ shortcode_tags ** your shortcode .. for example `` 'productsliders' => 'productSliders' `` ... the first is the name of the shortcode ([productsliders id = "50,51"]) ... the second is the method in the controller ** catalog / controller / shortcode / shortcode.php **
2.in the controller ** catalog / controller / shortcode / shortcode.php ** create the specified method ** productSliders **, which should return the desired html markup
3.in the desired controller, for example ** / catalog / controller / information / heating.php **, process the text `` $ this-> load-> model ('shortcode / shortcode');
                                                                                                                           $ data ['description'] = $ this-> model_shortcode_shortcode-> get ($ data ['description']); ``

In the editor, put ** [productsliders product_id = "50,51"] ** 
