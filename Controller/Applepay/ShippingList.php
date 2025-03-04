<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Controller\Applepay;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Model\ApplePay\Shipping;

class ShippingList implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var JsonResultFactory
     */
    private $jsonResultFactory;
    /**
     * @var Shipping
     */
    private $shipping;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ShippingList constructor.
     *
     * @param RequestInterface $request
     * @param Shipping $shipping
     * @param JsonSerializer $jsonSerializer
     * @param JsonResultFactory $jsonResultFactory
     * @param LoggerInterface $logger
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
        $result = $this->jsonResultFactory->create();

        try {
            $data = $this->getRequestData();

            if (!isset($data['shippingContact'])) {
                $errorMessage = __('Please select a different address');
                $this->logger->error((string) $errorMessage);
                $response = [
                    'success' => false,
                    'is_exception' => false,
                    'errorCode' => 'addressUnserviceable',
                    'contactField' => 'addressLines',
                    'message' => (string) $errorMessage,
                    'newTotal' => [
                        'label' => 'MERCHANT',
                        'amount' => 0
                    ],
                    'newShippingMethods' => [],
                    'newLineItems' => []
                ];
                $result->setHeader('Content-type', 'application/json');
                $result->setData($response);

                return $result;
            }
            // Validate every address contact field due ApplePay documentation.
            foreach ($data['shippingContact'] as $contactField => $fieldValue) {
                if ($this->canValidateField($contactField)) {
                    if (empty($fieldValue)) {
                        $errorMessage = __('Shipping Address Invalid');
                        $response = [
                            'success' => false,
                            'is_exception' => false,
                            'errorCode' => 'shippingContactInvalid',
                            'contactField' => $contactField,
                            'message' => (string) $errorMessage,
                            'newTotal' => [
                                'label' => 'MERCHANT',
                                'amount' => 0
                            ],
                            'newShippingMethods' => [],
                            'newLineItems' => []
                        ];
                        $result->setHeader('Content-type', 'application/json');
                        $result->setData($response);

                        return $result;
                    }
                }
            }

            // Pass over the shipping destination
            $this->shipping->setDataToQuote($data['shippingContact']);

            // Retrieve the shipping rates available for this quote
            $shippingMethodsForApplePay = $this->getShippingMethodsForApplePay();
            $grandTotalForApplePay = $this->getGrandTotal();

            if (count($shippingMethodsForApplePay) === 1 && isset($shippingMethodsForApplePay[0]['amount'])) {
                // If we have only one ShippingMethod we should set it to quote.
                $this->shipping->setShippingMethodToQuote($shippingMethodsForApplePay[0]);
            }
            $rowItemsApplePay = $this->getRowItems();
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
            $errorMessage = __('Something went wrong. Please contact support for assistance.');
            $response = [
                'success' => false,
                'is_exception' => true,
                'exception_message' => $e->getMessage(),
                'errorCode' => '',
                'contactField' => '',
                'message' => $errorMessage,
                'newTotal' => [
                    'label' => 'MERCHANT',
                    'amount' => 0
                ],
                'newShippingMethods' => [],
                'newLineItems' => []
            ];
            $result->setHeader('Content-type', 'application/json');
            $result->setData($response);

            return $result;
        }

        // Build response
        $response = [
            'success' => true,
            'newShippingMethods'    => ($shippingMethodsForApplePay)?? [],
            'newTotal'              => $grandTotalForApplePay,
            'newLineItems'          => $rowItemsApplePay,
        ];

        // Return JSON response
        $result->setHeader('Content-type', 'application/json');
        $result->setData($response);

        return $result;
    }

    /**
     * @return array|null
     */
    public function getRequestData()
    {
        return $this->jsonSerializer->unserialize($this->request->getContent());
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
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
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $request->isPost();
    }

    /**
     * @return array
     */
    public function getShippingMethodsForApplePay(): array
    {
        return $this->shipping->getShippingMethods();
    }

    /**
     * @return array
     */
    public function getGrandTotal(): array
    {
        return $this->shipping->getGrandTotal();
    }

    /**
     * @return array
     */
    public function getRowItems(): array
    {
        return $this->shipping->getRowItems();
    }

    /**
     * @param string $contactField
     * @return bool
     */
    public function canValidateField(string $contactField): bool
    {
        $requiredFields = [
            'administrativeArea',
            'country',
            'countryCode',
            'locality',
            'postalCode'
        ];

        return in_array($contactField, $requiredFields);
    }
}
