<?php

require_once(dirname(__FILE__).'/cm_webservice_announcements.php');
require_once(dirname(__FILE__).'/cm_soap.php');

/**
 * Configures the WSCourse SOAP service
 */
$s = WSCMSoapServer::singleton();



$s->register(
	'WSCMAnnouncements.get_announcements_id',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'course_code' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o ID dos anuncios visiveis a um usuario de uma disciplina.'
        
);

$s->register(
	'WSCMAnnouncements.get_announcement_data',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'course_code' => 'xsd:string',
                'announcement_id' => 'xsd:string',
                'field' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o conteudo do campo informado de um anuncio de chave ID. Campos retornaveis: sender, date, title e content'

);



