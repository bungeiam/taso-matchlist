<?php
/**
 * Uninstall script for Taso Matchlist.
 *
 * @package Taso_Matchlist
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * Tässä vaiheessa ei poisteta automaattisesti asetuksia tai transienteja,
 * koska lisäosa on vielä kehitysvaiheessa.
 *
 * Myöhemmin voidaan päättää, poistetaanko esimerkiksi:
 * - taso_matchlist_api_key
 * - taso_matchlist_club_id
 * - taso_matchlist_days_ahead
 * - taso_matchlist_cache_minutes
 */