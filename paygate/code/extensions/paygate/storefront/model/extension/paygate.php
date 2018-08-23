<?php
/*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 */
if ( !defined( 'DIR_CORE' ) ) {
    header( 'Location: static_pages/' );
}

class ModelExtensionPayGate extends Model
{

    public $data = array();

    public function getMethod( $address )
    {
        $this->load->language( 'paygate/paygate' );

        if ( $this->config->get( 'paygate_status' ) ) {

            $sql = "SELECT * FROM " . $this->db->table( 'zones_to_locations' ) . "
    		         WHERE location_id = '" . (int) $this->config->get( 'paygate_location_id' ) . "'
    		           AND country_id = '" . (int) $address['country_id'] . "'
    		           AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')";
            $query = $this->db->query( $sql );

            if ( !$this->config->get( 'paygate_location_id' ) ) {
                $status = true;
            } elseif ( $query->num_rows ) {
                $status = true;
            } else {
                $status = false;
            }

            if ( $status ) {
                // check for currency and looking for ZAR
                $all_currencies = $this->currency->getCurrencies();
                $found          = false;
                foreach ( $all_currencies as $curr ) {
                    if ( !$curr['status'] ) {
                        continue;
                    }
                    if ( strtolower( $curr['code'] ) == 'zar' ) {
                        $found = true;
                        break;
                    }
                }

                // disable payment if currency not found
                if ( !$found ) {
                    $this->log->write( 'PayGate.co.za error! Currency with code "ZAR" needed! ' );
                    $status = false;
                }
            }

        } else {
            $status = false;
        }

        $method_data = array();

        if ( $status ) {
            $method_data = array(
                'id'         => 'paygate',
                'title'      => $this->language->get( 'text_title' ),
                'sort_order' => $this->config->get( 'paygate_sort_order' ),
            );
        }

        return $method_data;
    }
}
