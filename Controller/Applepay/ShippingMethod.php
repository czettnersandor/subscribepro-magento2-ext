<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Controller\Applepay;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Model\ApplePay\Shipping;

class ShippingMethod implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Shipping
     */
    private $shipping;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var JsonResultFactory
     */
    private $jsonResultFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ShippingMethod constructor.
     *
     * @param RequestInterface  $request
     * @param Shipping          $shipping
     * @param JsonSerializer    $jsonSerializer
     * @param JsonResultFactory $jsonResultFactory
     * @param LoggerInterface   $logger
     */
    public function __construct(
        RequestInterface $request,
        Shipping $shipping,
        JsonSerializer $jsonSerializer,
        JsonResultFactory $jsonResultFactory,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->shipping = $shipping;
        $this->jsonSerializer = $jsonSerializer;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        // Return JSON response
        $result = $this->jsonResultFactory->create();
        $result->setHeader('Content-type', 'application/json');
        $errorMessage = __('Shipping method error. Please select a different shipping method.');

        try {
            $this->implementExectute($result);
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
            $response = [
                'success' => false,
                'is_exception' => true,
                'exception_message' => $e->getMessage(),
                'errorCode' => '',
                'contactField' => '',
                'message' => (string) $errorMessage,
                'newTotal' => [
                    'label' => 'MERCHANT',
                    'amount' => 0
                ],
                'newLineItems' => []
            ];
            $result->setData($response);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getRequestData()
    {
        return $this->jsonSerializer->unserialize($this->request->getContent());
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $resultRedirect = $this->jsonResultFactory->create();
        $resultRedirect->setHttpResponseCode(401);

        return new InvalidRequestException(
            $resultRedirect,
            [__('Invalid Post Request.')]
        );
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $request->isPost();
    }

    /**
     * @inheritdoc
     */
    public function getRowItems(): array
    {
        return $this->shipping->getRowItems();
    }

    /**
     * @inheritdoc
     */
    public function getGrandTotal(): array
    {
        return $this->shipping->getGrandTotal();
    }

    /**
     * Set shipping method on quote.
     *
     * @param ResultInterface $result
     *
     * @return void
     * @throws LocalizedException
     */
    private function implementExectute(ResultInterface $result)
    {
        // Get JSON POST
        $data = $this->getRequestData();

        if (!isset($data['shippingMethod'])) {
            throw new LocalizedException(__("'shippingMethod' key not set!"));
        }

        // Set shipping method selection
        $this->shipping->setShippingMethodToQuote($data['shippingMethod']);

        // Build up our response
        $response = [
            'success' => true,
            'newTotal' => $this->getGrandTotal(),
            'newLineItems' => $this->getRowItems(),
        ];

        $result->setData($response);
    }
}
