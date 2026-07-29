<?php

defined( 'ABSPATH' ) || exit;

interface Doc_Vista_Import_Adapter {

    public function supports( $data );

    public function normalize( $data );
}
