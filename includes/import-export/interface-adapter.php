<?php

defined( 'ABSPATH' ) || exit;

interface Zipped_Docs_Import_Adapter {

    public function supports( $data );

    public function normalize( $data );
}
