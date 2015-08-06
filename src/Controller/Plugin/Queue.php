<?php
/**
 * Stakhanovist
 *
 * @link        https://github.com/stakhanovist/zf2-module
 * @copyright   Copyright (c) 2015, Stakhanovist
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Stakhanovist\Module\Controller\Plugin;

use Stakhanovist\Queue\Exception as QueueException;
use Stakhanovist\Queue\Message\Message;
use Stakhanovist\Queue\Message\MessageIterator;
use Stakhanovist\Queue\Parameter\ReceiveParametersInterface;
use Stakhanovist\Queue\Parameter\SendParametersInterface;
use Stakhanovist\Queue\QueueClientInterface;
use Stakhanovist\Queue\QueueInterface;
use Zend\Http\Request;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\MessageInterface;

/**
 * Class Queue
 */
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
     * @param $queue
     * @return $this
     * @throws QueueException\InvalidArgumentException
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
            throw new QueueException\InvalidArgumentException(sprintf(
                'Invalid queue, a string or an instace of "%s" is expected; given "%s"',
                QueueClientInterface::class,
                is_object($queue) ? get_class($queue) : gettype($queue)
            ));
        }

        $this->queue = $queue;
        return $this;
    }

    /**
     * Send a message to the queue
     *
     * @param $message
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
     * @param $message
     * @param int $maxMessages
     * @param ReceiveParametersInterface $params
     * @return MessageIterator
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
        if ($params !== null) {
            $message->setMetadata($params);
        }

        $this->getQueue()->send($message, $sendParams);
        return $message;
    }

    /**
     * Send an HTTP request message
     *
     * @param $request
     * @param SendParametersInterface $sendParams
     * @return MessageInterface
     * @throws QueueException\InvalidArgumentException
     */
    public function http($request, SendParametersInterface $sendParams = null)
    {
        if (is_string($request)) {
            $req = new Request();
            $req->setUri($request);
            $request = $req;
        }

        if (!$request instanceof Request) {
            throw new QueueException\InvalidArgumentException(sprintf(
                'Invalid request, a string URI or an instace of "%s" expected; given "%s"',
                Request::class,
                is_object($request) ? get_class($request) : gettype($request)
            ));
        }

        $message = $this->getQueue()->send($request, $sendParams);
        return $message;
    }
}
