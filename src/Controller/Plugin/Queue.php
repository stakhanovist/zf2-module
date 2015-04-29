<?php
/**
 * Stakhanovist
 *
 * @link        https://github.com/stakhanovist/zf2-module
 * @copyright   Copyright (c) 2015, Stakhanovist
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Stakhanovist\Module\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;
use Stakhanovist\Queue\QueueClientInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Stakhanovist\Queue\QueueInterface;
use Stakhanovist\Queue\Parameter\SendParametersInterface;
use Zend\Stdlib\MessageInterface;
use Stakhanovist\Queue\Message\Message;
use Stakhanovist\Queue\Parameter\ReceiveParametersInterface;
use Zend\Http\Request;

class Queue extends AbstractPlugin implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var ServiceManager
     */
    protected $queueServiceLocator = null;

    /**
     * @var QueueClientInterface
     */
    protected $queue;

    /**
     * Set the queue service locator instance
     *
     * @param ServiceLocatorInterface $queueServiceLocator
     * @return $this
     */
    public function setQueueServiceLocator(ServiceLocatorInterface $queueServiceLocator)
    {
        $this->queueServiceLocator = $queueServiceLocator;
        return $this;
    }

    /**
     * Retrieve the queue service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getQueueServiceLocator()
    {
        if (!$this->queueServiceLocator) {
            $sl = $this->getServiceLocator();

            if ($sl instanceof AbstractPluginManager) {
                $sl = $sl->getServiceLocator();
            }

            if (!$sl instanceof ServiceLocatorInterface) {
                throw new \RuntimeException('A queue service locator is required');
            }

            $this->queueServiceLocator = $sl;
        }

        return $this->queueServiceLocator;
    }


    /**
     * Retrieve the current queue client
     *
     * @return QueueClientInterface
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @param mixed $queue
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function __invoke($queue)
    {
        if ($queue instanceof QueueInterface && !$queue instanceof QueueClientInterface) {
            $queue = $queue->getName();
        }

        if (is_string($queue)) {
            $queue = $this->getQueueServiceLocator()->get($queue);
        }

        if (!$queue instanceof QueueClientInterface) {
            throw new \InvalidArgumentException('Invalid $queue: must be a string or an instace of ' . QueueClientInterface::class);
        }

        $this->queue = $queue;
        return $this;
    }


    /**
     * Send a message to the queue
     *
     * @param mixed $message
     * @param SendParametersInterface $params
     * @return MessageInterface
     */
    public function send($message, SendParametersInterface $params = null)
    {
        return $this->getQueue()->send($message, $params);
    }

    /**
     * Receive messages from the queue
     *
     * @param unknown $message
     * @param number $maxMessages
     * @param ReceiveParametersInterface $params
     */
    public function receive($message, $maxMessages = 1, ReceiveParametersInterface $params = null)
    {
        return $this->getQueue()->receive($maxMessages, $params);
    }

    /**
     * Create a send a message in order to dispatch another controller
     *
     * Expect that a worker will process the message using the forward strategy
     *
     * @param  string $name Controller name; either a class name or an alias used in the DI container or service locator
     * @param  null|array $params Parameters with which to seed a custom RouteMatch object for the new controller
     * @param  SendParametersInterface $sendParams
     * @return MessageInterface
     */
    public function dispatch($name, array $params = null, SendParametersInterface $sendParams = null)
    {
        $message = new Message(); //TODO: use a custom message class?
        $message->setContent($name);
        if($params !== null) {
            $message->setMetadata($params);
        }

        $this->getQueue()->send($message, $sendParams);
        return $message;
    }

    /**
     * Send an HTTP request message
     *
     * @param mixed $request
     * @param SendParameters $sendParams
     * @throws \InvalidArgumentException
     * @return MessageInterface
     */
    public function http($request, SendParametersInterface $sendParams = null)
    {
        if(is_string($request)) {
            $req = new Request();
            $req->setUri($request);
            $request = $req;
        }

        if (!$request instanceof Request) {
            throw new \InvalidArgumentException('Invalid $request: must be an URI as string or an instace of ' . Request::class);
        }

        $message = $this->getQueue()->send($request, $sendParams);
        return $message;
    }
}
