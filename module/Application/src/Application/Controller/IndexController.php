<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $class = new \ReflectionClass($this);
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        $api = [];

        for ($i = 0; $i < count($methods); $i++) {
            $methodName = $methods[$i]->getName();
            if (substr($methodName, -6) !== 'Action') {
                continue;
            }

            if (in_array($methodName, array('indexAction', 'notFoundAction', 'getMethodFromAction'))) {
                continue;
            }

            $api[] = substr($methodName, 0, strlen($methodName) - 6);

            // echo substr($methods[$i]->getName(), -6);
        }

        return new ViewModel(array(
            'methods' => $api
        ));        
    }

    public function babiesAction()
    {
        return new JsonModel(
            array(
                'success' => true,
                'babies' => array(
                    array("name"=>"Xander", "dob"=>"4/9/2013", "interval"=>"180", "remind_interval"=>"20", "id"=>"1")
                )
            )
        );
    }

    public function saveAction()
    {
        $json = Json::decode($this->getRequest()->getContent());
        return new JsonModel(array(
            'success' => true,
            'baby' => $json
        ));
    }
}
