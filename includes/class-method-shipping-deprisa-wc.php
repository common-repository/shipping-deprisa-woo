<?php


class WC_Shipping_Method_Shipping_Deprisa_WC extends WC_Shipping_Method
{
    public string $debug;

    public string $code_client;

    public string $code_center;

    public string $state_sender;

    public string $city_sender;

    public bool $is_test;

    public string $guide_free_shipping;

    public string $license_key;

    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->id                 = 'shipping_deprisa_wc';
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __( 'Deprisa' );
        $this->method_description = __( 'Deprisa empresa transportadora de Colombia' );
        $this->title = $this->get_option('title');

        $this->supports = array(
            'settings',
            'shipping-zones'
        );

        $this->init();

        $this->debug = $this->get_option( 'debug' );
        $this->is_test = (bool)$this->get_option( 'environment' );
        $this->state_sender = $this->get_option( 'state_sender' );
        $this->city_sender = $this->get_option( 'city_sender' );
        $this->guide_free_shipping =  $this->get_option( 'guide_free_shipping' );
        $this->license_key = $this->get_option('license_key');

        if($this->is_test){
            $this->code_client = $this->get_option( 'sandbox_code_client' );
            $this->code_center = $this->get_option( 'sandbox_code_center' );
        }else{
            $this->code_client = $this->get_option( 'code_client' );
            $this->code_center = $this->get_option( 'code_center' );
        }

    }

    public function is_available($package): bool
    {
        return parent::is_available($package) &&
            !empty($this->code_client) &&
            !empty($this->code_center);
    }

    public function init(): void
    {
        // Load the settings API.
        $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings.
        $this->init_settings(); // This is part of the settings API. Loads settings you previously init.
        // Save settings in admin if you have any defined.
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields(): void
    {
        $this->form_fields = include(dirname(__FILE__) . '/admin/settings.php');
    }

    public function admin_options(): void
    {
        ?>
        <h3><?php echo $this->title; ?></h3>
        <p><?php echo $this->method_description; ?></p>
        <table class="form-table">
            <?php
            if (!empty($this->code_client) && !empty($this->code_center))
                Shipping_Deprisa_WC::test_connection();
            $this->generate_settings_html();
            ?>
        </table>
        <?php
    }

    public function calculate_shipping($package = array()): void
    {
        $country = $package['destination']['country'];
        $state = $package['destination']['state'];
        $city = $package['destination']['city'];
        $state = Shipping_Deprisa_WC::clean_string($city) === 'Bogota D.C' ? 'BOG' : $state;
        $post_code = $package['destination']['postcode'];

        if($country !== 'CO') return;

        if(empty($post_code) ||
            !Shipping_Deprisa_WC::is_acepted_post_code($state, $post_code)
        ){
            $post_code_472 = <<<HTML
            <p>Código Postal requerido*</p>
            <p>Consulte su código postal en <a target="_blank" href="http://visor.codigopostal.gov.co/472/visor/">Visor Codigo Postal 4-72 </a></p>
            HTML;
            wc_add_notice( $post_code_472, 'error' );
            return;
        }

        $params = [
            'TIPO_ENVIO' => 'N',
            'POBLACION_REMITENTE' => Shipping_Deprisa_WC::get_city($this->city_sender),
            'PAIS_DESTINATARIO' => '057',
            'POBLACION_DESTINATARIO' => Shipping_Deprisa_WC::get_city($package['destination']['city']),
            'INCOTERM' => '', //(SI para Inter)
            'TIPO_MERCANCIA' => '',
            'CONTENEDOR_MERCANCIA' => '', //(SI para Inter) S / C (Indica: sobre o caja)
            'TIPO_MONEDA' => 'COP'
        ];

        $items = $package['contents'];
        $data_products = Shipping_Deprisa_WC::data_products($items);

        $params = array_merge($params, $data_products);

        if ($this->debug === 'yes')
            shipping_deprisa_wc_sd()->log($params);

        $concepts = Shipping_Deprisa_WC::liquidation($params);

        if (empty($concepts)) return;

        if (count($concepts) === count($concepts, COUNT_RECURSIVE)){
            $this->add_rate(
                [
                    'id'      => $this->id,
                    'label'   => $this->title,
                    'cost'    => $concepts['TOTAL'],
                    'package' => $package
                ]
            );
            return;
        }

        foreach ($concepts as $concept){
            if(!isset($concept['TOTAL'])) continue;
            $this->add_rate(
                [
                    'id'      => "$this->id-{$concept['PRODUCTO_CODIGO']}",
                    'label' => $concept['PRODUCTO_DESCRIPCION'],
                    'cost'    => $concept['TOTAL']
                ]
            );
        }


    }
}