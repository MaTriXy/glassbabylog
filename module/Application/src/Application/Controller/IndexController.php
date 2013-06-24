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

    protected function getGoogleClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName("glassbabylog");

        // Visit https://code.google.com/apis/console?api=plus to generate your
        // client id, client secret, and to register your redirect uri.
        $client->setClientId($this->config['client_id']);
        $client->setClientSecret($this->config['client_secret']);
        $client->setRedirectUri($this->config['redirect_uri']);
        $client->setDeveloperKey($this->config['developer_key']);
        return $client;
    }

    public function indexAction()
    {
        session_start();

        $state = md5(rand());
        $_SESSION['state'] = $state;

        return new ViewModel(array(
          'CLIENT_ID' => $this->config['client_id'],
          'STATE'     => $state
        ));
    }


    // Upgrade given auth code to token, and store it in the session.
    // POST body of request should be the authorization code.
    // Example URI: /connect?state=...&gplus_id=...
    public function connectAction() 
    {
        session_start();

        $token = $_SESSION['token'];
        if(empty($token)) {

          // Ensure that this is no request forgery going on, and that the user
          // sending us this connect request is the user that was supposed to.
          if ($this->getRequest()->getQuery('state') != $_SESSION['state']) {
            $this->getResponse()->setStatusCode(401);   
            $this->getResponse()->setContent('Invalid state parameter');
            return $this->getResponse();
          }

          $client = $this->getGoogleClient();

          // Normally the state would be a one-time use token, however in our
          // simple case, we want a user to be able to connect and disconnect
          // without reloading the page.  Thus, for demonstration, we don't
          // implement this best practice.
          //$app['session']->set('state', '');

          $code = $this->getRequest()->getContent();
          // Exchange the OAuth 2.0 authorization code for user credentials.
          $client->authenticate($code);
          $token = json_decode($client->getAccessToken());

          // You can read the Google user ID in the ID token.
          // "sub" represents the ID token subscriber which in our case 
          // is the user ID. This sample does not use the user ID.
          $attributes = $client->verifyIdToken($token->id_token, $this->config['client_id'])
              ->getAttributes();
          $gplus_id = $attributes["payload"]["sub"];

          // Store the token in the session for later use.
          $_SESSION['token'] = json_encode($token);
          $response = 'Successfully connected with token: ' . print_r($token, true);
        }

        $this->getResponse()->setContent($respone);
        return $this->getResponse();
    }

    // Get list of people user has shared with this app.
    public function peopleAction()
    {
        session_start();

        $client = $this->getGoogleClient();

        $plus = new \Google_PlusService($client);

        $token = $_SESSION['token'];
        if (empty($token)) {
            $this->getResponse()->setStatusCode(401);
            return $this->getResponse();
          // return new Response('Unauthorized request', 401);
        }
        $client->setAccessToken($token);
        $people = $plus->people->listPeople('me', 'visible', array());
        return new JsonModel($people);
    }


    public function disconnectAction()
    {
        session_start();

        $client = $this->getGoogleClient();

        $token = json_decode($_SESSION['token'])->access_token;
        $client->revokeToken($token);

        // Remove the credentials from the user's session.
        $_SESSION['token'] = '';
        $this->getResponse()->setContent('Successfully disconnected');
        return $this->getResponse();
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
