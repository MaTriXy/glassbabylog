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
use Application\ConfigAwareInterface;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class IndexController extends AbstractActionController
  implements ConfigAwareInterface
{
    protected $config;
 
    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function indexAction()
    {
        session_start();

        $client = new \Google_Client();
        $client->setApplicationName("Google+ PHP Starter Application");

        // Visit https://code.google.com/apis/console?api=plus to generate your
        // client id, client secret, and to register your redirect uri.
        $client->setClientId($this->config['client_id']);
        $client->setClientSecret($this->config['client_secret']);
        $client->setRedirectUri($this->config['redirect_uri']);
        $client->setDeveloperKey($this->config['developer_key']);
        $plus = new \Google_PlusService($client);

        if (isset($_GET['logout'])) {
          unset($_SESSION['token']);
        }

        if (isset($_GET['code'])) {
          if (strval($_SESSION['state']) !== strval($_GET['state'])) {
            die("The session state did not match.");
          }
          $client->authenticate();
          $_SESSION['token'] = $client->getAccessToken();
          $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
          header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
        }

        if (isset($_SESSION['token'])) {
          $client->setAccessToken($_SESSION['token']);
        }

        if ($client->getAccessToken()) {
          $me = $plus->people->get('me');
          print "Your Profile: <pre>" . print_r($me, true) . "</pre>";

          $params = array('maxResults' => 100);
          $activities = $plus->activities->listActivities('me', 'public', $params);
          print "Your Activities: <pre>" . print_r($activities, true) . "</pre>";
          
          $params = array(
            'orderBy' => 'best',
            'maxResults' => '20',
          );
          $results = $plus->activities->search('Google+ API', $params);
          foreach($results['items'] as $result) {
            print "Search Result: <pre>{$result['object']['content']}</pre>\n";
          }

          // The access token may have been updated lazily.
          $_SESSION['token'] = $client->getAccessToken();
        } else {
          $state = mt_rand();
          $client->setState($state);
          $_SESSION['state'] = $state;

          $authUrl = $client->createAuthUrl();
          print "<a class='login' href='$authUrl'>Connect Me!</a>";
        }

        return new ViewModel(array(
          'CLIENT_ID' => $this->config['client_id']
        ));
    }

    public function listAction()
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
