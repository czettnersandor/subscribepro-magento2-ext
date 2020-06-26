<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Api\CartManagementInterface;

class QuoteManagement implements CartManagementInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * QuoteManagement constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param QuoteFactory $quoteFactory
     * @param StoreManagerInterface $storeManager
     * @param CartRepositoryInterface $quoteRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        QuoteFactory $quoteFactory,
        StoreManagerInterface $storeManager,
        CartRepositoryInterface $quoteRepository,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * @param int $customerId
     * @return int Quote ID
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createEmptyCartForCustomer($customerId)
    {
        $this->logger->info('Creating empty quote for customer ' . $customerId);
        $storeId = $this->storeManager->getStore()->getStoreId();
        $quote = $this->createCustomerCart($customerId, $storeId);

        try {
            $this->logger->info('Persisting new quote');
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__("The quote can't be created. " . $e->getMessage()));
        }
        $this->logger->info("Created quote " . $quote->getId());
        return (int)$quote->getId();
    }

    /**
     * @param int $customerId
     * @param int $storeId
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createCustomerCart($customerId, $storeId)
    {
        $customer = $this->customerRepository->getById($customerId);
        $quote = $this->quoteFactory->create();
        $quote->setStoreId($storeId);
        $quote->setCustomer($customer);
        $quote->setCustomerIsGuest(0);

        return $quote;
    }

    /**
     * @param int $cartId
     * @return null
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function deactivateCustomerCart($cartId)
    {
        $this->logger->info('Deactivating quote ' . $cartId);
        $quote = $this->quoteRepository->get($cartId);
        $quote->setIsActive(false);
        try {
//            $this->logger->info('Saving quote with $quote->save()');
//            $quote->save();
            $this->logger->info('Persisting changed quote in quote repository');
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            throw new CouldNotSaveException(__("The quote can't be deactivated. "));
        }
    }
}
