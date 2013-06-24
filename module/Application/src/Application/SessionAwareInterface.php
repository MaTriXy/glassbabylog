<?php
namespace Application;

interface SessionAwareInterface 
{

    public function setSession(\Zend\Session\Container $session);
}