<?php

use Saulmoralespa\Deprisa\Client;

class Shipping_Deprisa_WC extends WC_Shipping_Method_Shipping_Deprisa_WC
{

    public Client $deprisa;

    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);
        $this->deprisa = new Client($this->code_client, $this->code_center);
        $this->upgrade_working_plugin();
    }

    public static function test_connection(): void
    {
        $instance = new self();

        try{

            $params = [
                'TIPO_ENVIO' => 'N',
                'NUMERO_BULTOS' => 1,
                'KILOS' => 5,
                'POBLACION_REMITENTE' => self::clean_string($instance->city_sender),
                'PAIS_DESTINATARIO' => '',
                'POBLACION_DESTINATARIO' => 'BOGOTA',
                'INCOTERM' => '',
                'CODIGO_SERVICIO'  => '',
                'LARGO' => 10,
                'ANCHO' => 20,
                'ALTO' => 15,
                'TIPO_MERCANCIA' => '',
                'CONTENEDOR_MERCANCIA' => '',
                'IMPORTE_VALOR_DECLARADO' => 5000,
                'TIPO_MONEDA' => 'COP'
            ];

            $liquidation = $instance->deprisa->liquidation($params);

            $params = [
                'GRABAR_ENVIO' => 'S',
                'CODIGO_ADMISION' => time(),
                'NUMERO_ENVIO' => '',
                'NUMERO_BULTOS' => 2,
                'NOMBRE_REMITENTE' => '',
                'DIRECCION_REMITENTE' => '',
                'PAIS_REMITENTE' => '057',
                'CODIGO_POSTAL_REMITENTE' => '110911',
                'POBLACION_REMITENTE' => 'BOGOTA',
                'TIPO_DOC_REMITENTE' => 'CC',
                'DOCUMENTO_IDENTIDAD_REMITENTE' => '73082468',
                'TELEFONO_CONTACTO_REMITENTE' => '3127534562',
                'DEPARTAMENTO_REMITENTE' => '',
                'EMAIL_REMITENTE' => 'aaa@ori.com',
                'NOMBRE_DESTINATARIO' => 'Pedro Perez',
                'DIRECCION_DESTINATARIO' => 'calle 50 N 3-23',
                'PAIS_DESTINATARIO' => '057',
                'CODIGO_POSTAL_DESTINATARIO' => '110911',
                'POBLACION_DESTINATARIO' => 'BOGOTA',
                'TIPO_DOC_DESTINATARIO' => 'CC',
                'DOCUMENTO_IDENTIDAD_DESTINATARIO' => '73082468',
                'PERSONA_CONTACTO_DESTINATARIO' => 'Raul Reyes',
                'TELEFONO_CONTACTO_DESTINATARIO' => '3127534562',
                'DEPARTAMENTO_DESTINATARIO' => '',
                'EMAIL_DESTINATARIO' => 'leireoo@gmail.com',
                'INCOTERM' => '',
                'RAZON_EXPORTAR' => '',
                'EMBALAJE' => '',
                'CODIGO_SERVICIO' => '3005',
                'KILOS' => 4,
                'VOLUMEN' => 0.5,
                'LARGO' => 10,
                'ANCHO' => 20,
                'ALTO' => 15,
                'NUMERO_REFERENCIA' => time(),
                'IMPORTE_REEMBOLSO' => 100000,
                'IMPORTE_VALOR_DECLARADO' => 1000,
                'TIPO_PORTES' => 'P',
                'OBSERVACIONES1' => 'Prueba de grabación en WEEX',
                'OBSERVACIONES2' => 'Prueba de grabación en WEEX 2',
                'TIPO_MERCANCIA' => 'P',
                'ASEGURAR_ENVIO' => 'S',
                'TIPO_MONEDA' => 'COP',
                /*'BULTOS_ADMISION' => [
                    'BULTO' => [
                        'REFERENCIA_BULTO_CLIENTE' => '111111',
                        'TIPO_BULTO' => '1425',
                        'LARGO' => 19,
                        'ANCHO' => 39,
                        'ALTO' => 29,
                        'VOLUMEN' => 9,
                        'KILOS' => 9,
                        'OBSERVACIONES' => 'obser bulto',
                        'CODIGO_BARRAS_CLIENTE' => '4534534534534'
                    ]
                ]*/
            ];

            //$instance->deprisa->admission($params);

        }catch (\Exception $exception){
            shipping_deprisa_wc_sd()->log($exception->getMessage());
        }
    }

    public static function clean_string($string): string
    {
        $not_permitted = array ("á","é","í","ó","ú","Á","É","Í",
            "Ó","Ú","ñ");
        $permitted = array ("a","e","i","o","u","A","E","I","O",
            "U","n");
        return str_replace($not_permitted, $permitted, $string);
    }

    public static function clean_city($city): string
    {
        return $city === 'Bogota D.C' ? 'Bogota' : $city;
    }

    public static function get_city(string $city_destination): string
    {
        $city_destination = self::clean_string($city_destination);
        return self::clean_city($city_destination);
    }

    public static function data_products(array $items, $guide = false) : array
    {
        $data['KILOS'] = 0;
        $data['LARGO'] = 0;
        $data['ANCHO'] = 0;
        $data['ALTO'] = 0;
        $data['IMPORTE_VALOR_DECLARADO'] = 0;
        $data['NUMERO_BULTOS'] = 1;

        foreach ($items as $item_id => $values) {
            $product_id = $guide ? $values['product_id'] : $values['data']->get_id();
            $product = wc_get_product( $product_id );

            if ( $values['variation_id'] > 0 &&
                in_array( $values['variation_id'], $product->get_children() ) &&
                wc_get_product( $values['variation_id'] )->get_weight() &&
                wc_get_product( $values['variation_id'] )->get_length() &&
                wc_get_product( $values['variation_id'] )->get_width() &&
                wc_get_product( $values['variation_id'] )->get_height())
                $product = wc_get_product( $values['variation_id'] );

            if (!$product || !$product->get_weight() || !$product->get_length()
                || !$product->get_width() || !$product->get_height())
                break;

            $data['ALTO'] += $product->get_height() * $values['quantity'];
            $data['LARGO'] = $product->get_length() > $data['LARGO'] ? $product->get_length() : $data['LARGO'];
            $data['ANCHO'] =  $product->get_width() > $data['ANCHO'] ? $product->get_width() : $data['ANCHO'];
            $data['KILOS'] += $product->get_weight() * $values['quantity'];

            $custom_price_product = get_post_meta($product_id, '_shipping_custom_price_product_smp', true);
            $data['IMPORTE_VALOR_DECLARADO'] += $custom_price_product ?: $product->get_price() * $values['quantity'];
        }

        return apply_filters('shipping_deprisa_data_products', $data, $items, $guide);
    }

    public static function is_acepted_post_code($state, $post_code): bool
    {
        if(strlen($post_code) !== 6) return false;
        $states =  include dirname(__FILE__) . '/states.php';
        $key = array_search($state, $states);

        return $key && preg_match("/^($key)/", $post_code);
    }

    public static function liquidation(array $params) : array
    {
        $res = [];

        try{
            $instance = new self();
            $res = $instance->deprisa->liquidation($params);
            return $res['RESPUESTA_COTIZACION'];
        }catch (\Exception $exception){
            shipping_deprisa_wc_sd()->log($exception->getMessage());
        }

        return $res;
    }

    public static function print_labels(array $params): array
    {
        $res = [];

        try{
            $instance = new self();
            $res = $instance->deprisa->labels($params);
        }catch (\Exception $exception){
            shipping_deprisa_wc_sd()->log($exception->getMessage());
        }

        return $res;
    }

    public static function tracking($shipping_number): array
    {
        $data = [];

        try {
            $instance = new self();
            $data = $instance->deprisa->tracking($shipping_number);
        }catch (\Exception $exception){
            shipping_deprisa_wc_sd()->log($exception->getMessage());
        }

        return $data;
    }

    public static function generate_admision($order_id, $old_status, $new_status, WC_Order $order) : mixed
    {
        $sub_orders = get_children( array( 'post_parent' => $order_id, 'post_type' => 'shop_order' ) );

        if ( $sub_orders ) {
            foreach ($sub_orders as $sub) {
                $order = new WC_Order($sub->ID);
                self::exec_guide($order, $new_status);
            }
        }else{
            self::exec_guide($order, $new_status);
        }

        return apply_filters( 'deprisa_generate_admision', $order_id, $old_status, $new_status, $order );
    }

    public static function exec_guide(WC_Order $order, $new_status): void
    {
        $shipping_number = get_post_meta($order->get_id(), 'admision_deprisa', true);
        $instance = new self();

        $order_id_origin = self::get_parent_id($order);
        $order_parent = new WC_Order($order_id_origin);

        if(empty($shipping_number) &&
            !empty($instance->license_key) &&
            $new_status === 'processing' &&
            ($order_parent->has_shipping_method($instance->id) ||
                $order_parent->has_shipping_method('free_shipping') &&
                $order_parent->get_shipping_total() == 0 &&
                $instance->guide_free_shipping === 'yes')){

            $admision = $instance->guide($order);

            if (!isset($admision['NUMERO_ENVIO']) && !$admision['NUMERO_ENVIO']) return;

            $shipping_number = $admision['NUMERO_ENVIO'];

            update_post_meta($order->get_id(), 'admision_deprisa', $shipping_number);

            $note = sprintf( __( 'Número de envío Deprisa: %d' ), $shipping_number );
            $order->add_order_note($note);

        }
    }

    public static function get_parent_id(WC_Order $order): int
    {
        return $order->get_parent_id() > 0 ? $order->get_parent_id() : $order->get_id();
    }

    public static function guide(WC_Order $order) :array
    {
        $instance = new self();

        $recipient_name = $order->get_shipping_first_name() ? $order->get_shipping_first_name() .
            " " . $order->get_shipping_last_name() : $order->get_billing_first_name() .
            " " . $order->get_billing_last_name();

        $recipient_address = $order->get_shipping_address_1() ? $order->get_shipping_address_1() .
            " " . $order->get_shipping_address_2() : $order->get_billing_address_1() .
            " " . $order->get_billing_address_2();

        $recipient_post_code = $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode();
        $recipient_city = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();
        $recipient_city = Shipping_Deprisa_WC::get_city($recipient_city);
        $recipient_state = $order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state();
        $recipient_state_name = WC()->countries->get_states( 'CO' )[$recipient_state];
        $recipient_state_name = Shipping_Deprisa_WC::clean_string($recipient_state_name);


        $order_id_origin = self::get_parent_id($order);

        $recipient_type_doc = get_post_meta( $order_id_origin, '_billing_type_document', true );
        $recipient_doc = get_post_meta( $order_id_origin, '_billing_dni', true );

        $items = $order->get_items();

        $data_products = Shipping_Deprisa_WC::data_products($items, true);

        $params = [
            'GRABAR_ENVIO' => 'S',
            'CODIGO_ADMISION' => $order->get_id(),
            'NUMERO_ENVIO' => '',
            'NOMBRE_REMITENTE' => '',
            'DIRECCION_REMITENTE' => '',
            'PAIS_REMITENTE' => '057',
            'CODIGO_POSTAL_REMITENTE' => '',
            'POBLACION_REMITENTE' => '',
            'TIPO_DOC_REMITENTE' => '',
            'DOCUMENTO_IDENTIDAD_REMITENTE' => '',
            'TELEFONO_CONTACTO_REMITENTE' => '',
            'DEPARTAMENTO_REMITENTE' => '',
            'EMAIL_REMITENTE' => '',
            'CLIENTE_DESTINATARIO' => '99999999',
            'CENTRO_DESTINATARIO' => '99',
            'NOMBRE_DESTINATARIO' => $recipient_name,
            'DIRECCION_DESTINATARIO' => $recipient_address,
            'PAIS_DESTINATARIO' => '057',
            'CODIGO_POSTAL_DESTINATARIO' => $recipient_post_code,
            'POBLACION_DESTINATARIO' => $recipient_city,
            'TIPO_DOC_DESTINATARIO' => $recipient_type_doc,
            'DOCUMENTO_IDENTIDAD_DESTINATARIO' => $recipient_doc,
            'PERSONA_CONTACTO_DESTINATARIO' => $recipient_name,
            'TELEFONO_CONTACTO_DESTINATARIO' => $order->get_billing_phone(),
            'DEPARTAMENTO_DESTINATARIO' => $recipient_state_name,
            'EMAIL_DESTINATARIO' => $order->get_billing_email(),
            'INCOTERM' => '',
            'RAZON_EXPORTAR' => '',
            'EMBALAJE' => '',
            'CODIGO_SERVICIO' => '3005',
            'VOLUMEN' => '',
            'NUMERO_REFERENCIA' => $order->get_id(),
            'IMPORTE_REEMBOLSO' => '',
            'TIPO_PORTES' => 'P', //P pago origen, D pogo destino
            'OBSERVACIONES1' => '',
            'TIPO_MERCANCIA' => '',
            'ASEGURAR_ENVIO' => 'S',
            'TIPO_MONEDA' => 'COP'
        ];

        $params = array_merge($params, $data_products);

        if ($instance->debug === 'yes')
            shipping_deprisa_wc_sd()->log($params);

        $response = [];

        try {
            $data = $instance->deprisa->admission($params);
            $response = $data['ADMISIONES']["RESPUESTA_ADMISION"];

        }catch (\Exception $exception){
            shipping_deprisa_wc_sd()->log($exception->getMessage());
        }

        return $response;

    }

    public function upgrade_working_plugin(): void
    {
        if (!empty($this->license_key)){

            $secret_key = '5c88321cdb0dc9.43606608';

            $api_params = array(
                'slm_action' => 'slm_check',
                'secret_key' => $secret_key,
                'license_key' => $this->license_key,
            );

            $siteGet = 'https://shop.saulmoralespa.com';

            $response = wp_remote_get(
                add_query_arg($api_params, $siteGet),
                array('timeout' => 60,
                    'sslverify' => true
                )
            );

            if (is_wp_error($response)){
                shipping_deprisa_wc_sd_notices( $response->get_error_message() );
                exit();
            }

            $data = json_decode(wp_remote_retrieve_body($response));


            //max_allowed_domains

            //registered_domains  array() registered_domain

            if (isset($data) && ($data->result === 'error' || $data->status === 'expired')){
                $this->update_option('license_key', '');
            }elseif (isset($data) && $data->result === 'success' && $data->status === 'pending'){

                $api_params = array(
                    'slm_action' => 'slm_activate',
                    'secret_key' => $secret_key,
                    'license_key' => $this->license_key,
                    'registered_domain' => get_bloginfo( 'url' ),
                    'item_reference' => urlencode($this->id),
                );

                $query = esc_url_raw(add_query_arg($api_params, $siteGet));
                $response = wp_remote_get($query,
                    array('timeout' => 60,
                        'sslverify' => true
                    )
                );

                if (is_wp_error($response)){
                    shipping_deprisa_wc_sd_notices( $response->get_error_message() );
                    exit();
                }

                $data = json_decode(wp_remote_retrieve_body($response));

                if($data->result === 'error')
                    $this->update_option('license_key', '');

            }

        }
    }

}