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
        $client->setApplicationName("glassbabylog");

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

    public function storeTokenAction()
    {
      session_start();
      $client = new \Google_Client();
      $client->setApplicationName("glassbabylog");

      // Visit https://code.google.com/apis/console?api=plus to generate your
      // client id, client secret, and to register your redirect uri.
      $client->setClientId($this->config['client_id']);
      $client->setClientSecret($this->config['client_secret']);
      $client->setRedirectUri($this->config['redirect_uri']);
      $client->setDeveloperKey($this->config['developer_key']);
      $plus = new \Google_PlusService($client);

      $request = $this->getRequest();
      $code = $request->getContent();
      // $gPlusId = $request->get['gplus_id'];
      // Exchange the OAuth 2.0 authorization code for user credentials.
      $client->authenticate($code);

      $token = json_decode($client->getAccessToken());
      var_dump($token);
      exit;
      // Verify the token
      $reqUrl = 'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' .
              $token->access_token;
      $req = new Google_HttpRequest($reqUrl);

      $tokenInfo = json_decode(
          $client::getIo()->authenticatedRequest($req)->getResponseBody());

      // If there was an error in the token info, abort.
      if ($tokenInfo->error) {
        $this->getResponse()->setStatusCode(500);
        $this->getResponse()->setContent($tokenInfo->error);
        return;
        // return new Response($tokenInfo->error, 500);
      }
      // Make sure the token we got is for the intended user.
      // if ($tokenInfo->userid != $gPlusId) {
      //   return new Response(
      //       "Token's user ID doesn't match given user ID", 401);
      // }
      // Make sure the token we got is for our app.
      if ($tokenInfo->audience != $this->config['client_id']) {
        $this->getResponse()->setStatusCode(401);
        $this->getResponse()->setContent("Token's client ID does not match app's.");
        return;
      }

      // Store the token in the session for later use.
      // $app['session']->set('token', json_encode($token));
      $_SESSION['token'] = json_encode($token);
      $this->getResponse()->setContent('Succesfully connected with token: ' . print_r($token, true));
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
