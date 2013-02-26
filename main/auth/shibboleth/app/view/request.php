<?php

namespace Shibboleth;

/**
 * Display the Request another status/additional rights. The request is emailed
 * to the shibboleth and platform administrators for processing.
 * 
 * Users such as staff that can be either student or teachers are presented with
 * this page upon first login. 
 * 
 * Other users - teachers, students - are directly logged-in.
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
$dir = dirname(__FILE__);
include_once("$dir/../../init.php");

ShibbolethController::instance()->request_status();