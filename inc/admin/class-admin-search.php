<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Admin_Search {

    private $settings;

    public function __construct(BM_Settings $settings) {
        $this->settings = $settings;
    }
}
