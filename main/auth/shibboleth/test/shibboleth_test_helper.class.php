<?php

namespace Shibboleth;

/**
 * Helper functions for the tests. Set up various dummy user types: teacher, student, etc.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
class ShibbolethTestHelper
{

    /**
     *
     * @return ShibbolethTestHelper 
     */
    public static function instance()
    {
        static $result = false;
        if (empty($result))
        {
            $result = new self();
        }
        return $result;
    }

    public function setup_teacher()
    {
        $_SERVER['Shib-SwissEP-UniqueID'] = 'usr_1';
        $_SERVER['Shib-EP-Affiliation'] = 'member;staff;faculty';
        $_SERVER['Shib-InetOrgPerson-givenName'] = 'John';
        $_SERVER['Shib-Person-surname'] = 'Doe';
        $_SERVER['Shib-InetOrgPerson-mail'] = 'john.doe@localhost.org';
        $_SERVER['persistent-id'] = 'idp!viewer!drea34çcv3d';
    }

    public function setup_student()
    {
        $_SERVER['Shib-SwissEP-UniqueID'] = 'usr_1';
        $_SERVER['Shib-EP-Affiliation'] = 'member';
        $_SERVER['Shib-InetOrgPerson-givenName'] = 'John';
        $_SERVER['Shib-Person-surname'] = 'Doe';
        $_SERVER['Shib-InetOrgPerson-mail'] = 'john.doe@localhost.org';
        $_SERVER['persistent-id'] = 'idp!viewer!drea34çcv3d';
    }

    public function setup_staff()
    {
        $id = uniqid();
        $_SERVER['Shib-SwissEP-UniqueID'] = 'usr_123456';
        $_SERVER['Shib-EP-Affiliation'] = 'member;staff';
        $_SERVER['Shib-InetOrgPerson-givenName'] = 'John Staff';
        $_SERVER['Shib-Person-surname'] = 'Doe';
        $_SERVER['Shib-InetOrgPerson-mail'] = 'john.staff.doe@localhost.org';
        $_SERVER['persistent-id'] = 'idp!viewer!usr_123456';
    }

    public function setup_new_student()
    {
        $id = uniqid();
        $_SERVER['Shib-SwissEP-UniqueID'] = 'usr_' . $id;
        $_SERVER['Shib-EP-Affiliation'] = 'member';
        $_SERVER['Shib-InetOrgPerson-givenName'] = 'John';
        $_SERVER['Shib-Person-surname'] = 'Doe' . $id;
        $_SERVER['Shib-InetOrgPerson-mail'] = 'john.' . $id . 'Doe@localhost.org';
        $_SERVER['persistent-id'] = 'idp!viewer!' . md5($id);
    }

    public function setup_new_student_no_email()
    {
        $id = uniqid();
        $_SERVER['Shib-SwissEP-UniqueID'] = 'usr_' . $id;
        $_SERVER['Shib-EP-Affiliation'] = 'member';
        $_SERVER['Shib-InetOrgPerson-givenName'] = 'John';
        $_SERVER['Shib-Person-surname'] = 'Doe' . $id;
        $_SERVER['Shib-InetOrgPerson-mail'] = '';
        $_SERVER['persistent-id'] = 'idp!viewer!' . md5($id);
    }

    public function setup_new_student_multiple_givenname()
    {
        $id = uniqid();
        $_SERVER['Shib-SwissEP-UniqueID'] = 'usr_' . $id;
        $_SERVER['Shib-EP-Affiliation'] = 'member';
        $_SERVER['Shib-InetOrgPerson-givenName'] = 'John;Alex;John Alex';
        $_SERVER['Shib-Person-surname'] = 'Doe' . $id;
        $_SERVER['Shib-InetOrgPerson-mail'] = 'john.' . $id . 'Doe@localhost.org';
        $_SERVER['persistent-id'] = 'idp!viewer!' . md5($id);
    }

    public function setup_new_teacher()
    {
        $id = uniqid();
        $_SERVER['Shib-SwissEP-UniqueID'] = 'usr_' . $id;
        $_SERVER['Shib-EP-Affiliation'] = 'member;staff;faculty';
        $_SERVER['Shib-InetOrgPerson-givenName'] = 'John';
        $_SERVER['Shib-Person-surname'] = 'Doe' . $id;
        $_SERVER['Shib-InetOrgPerson-mail'] = 'john.' . $id . 'Doe@localhost.org';
        $_SERVER['persistent-id'] = 'idp!viewer!' . md5($id);
    }

    public function setup_new_staff()
    {
        $id = uniqid();
        $_SERVER['Shib-SwissEP-UniqueID'] = 'usr_' . $id;
        $_SERVER['Shib-EP-Affiliation'] = 'member;staff';
        $_SERVER['Shib-InetOrgPerson-givenName'] = 'John';
        $_SERVER['Shib-Person-surname'] = 'Doe' . $id;
        $_SERVER['Shib-InetOrgPerson-mail'] = 'john.' . $id . 'Doe@localhost.org';
        $_SERVER['persistent-id'] = 'idp!viewer!' . md5($id);
    }

    public function setup_new_no_affiliation()
    {
        $id = uniqid();
        $_SERVER['Shib-SwissEP-UniqueID'] = 'usr_' . $id;
        $_SERVER['Shib-EP-Affiliation'] = '';
        $_SERVER['Shib-InetOrgPerson-givenName'] = 'John';
        $_SERVER['Shib-Person-surname'] = 'Doe' . $id;
        $_SERVER['Shib-InetOrgPerson-mail'] = 'john.' . $id . 'Doe@localhost.org';
        $_SERVER['persistent-id'] = 'idp!viewer!' . md5($id);
    }

    public function setup_new_minimal_data()
    {
        $id = uniqid();
        $_SERVER['Shib-SwissEP-UniqueID'] = 'usr_' . $id;       
        $_SERVER['Shib-InetOrgPerson-givenName'] = 'John';
        $_SERVER['Shib-Person-surname'] = 'Doe' . $id;
    }

}