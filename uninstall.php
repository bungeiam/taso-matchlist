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
 * koska lisäosa on vasta alkuvaiheessa.
 *
 * Myöhemmässä vaiheessa voidaan päättää erikseen:
 * - poistetaanko kaikki asetukset uninstallissa
 * - vai jätetäänkö ne talteen mahdollista uudelleenasennusta varten
 */