<?php

namespace WHMCS\Module\Registrar\Spaceship;

require __DIR__ . '/vendor/autoload.php';

use Spaceship\Enums\NameserverProviders;
use Spaceship\Enums\PrivacyLevel;
use Spaceship\Exception\SpaceshipException;
use Spaceship\Params\CreateContactParams;
use Spaceship\Params\NameserverParams;
use Spaceship\Params\PrivacyProtectionParams;
use Spaceship\Params\TransferLockParams;
use Spaceship\Params\UpdateContactParams;
use Spaceship\Response\ContactResponse;
use Spaceship\Response\DomainResponse;
use Spaceship\SpaceshipAPI;

class SpaceshipWHMCS
{
    private SpaceshipAPI $api;

    /**
     * Initialize service with WHMCS module params
     *
     * @param array $params WHMCS module parameters
     */
    public function __construct(array $params)
    {
        $this->api = new SpaceshipAPI(
            $params['ApiKey'],
            $params['ApiSecret']
        );
    }

    /**
     * Get domain information from registrar
     *
     * @param array $params WHMCS parameters
     * @return DomainResponse WHMCS response array
     */
    public function getDomainInfo(array $params): DomainResponse
    {
        return $this->api->domain($params['sld'] . '.' . $params['tld']);
    }

    /**
     * Get Nameservers for WHMCS
     *
     * @param array $params WHMCS parameters
     * @return array Array with nameservers or error
     */
    public function getNameservers(array $params): array
    {
        try {
            // Get domain information from API
            $response = $this->getDomainInfo($params);

            // Check if response has nameservers
            if (empty($response->nameserverHosts())) {
                return [
                    'error' => 'No nameservers found for this domain',
                ];
            }

            // Format response for WHMCS
            $result = [];
            foreach ($response->nameserverHosts() as $index => $ns) {
                // WHMCS expects nameservers in ns1, ns2, ns3, ns4, ns5 format
                $nsNumber = $index + 1;
                if ($nsNumber <= 5) { // WHMCS supports up to 5 nameservers
                    $result['ns' . $nsNumber] = $ns;
                }
            }

            // If no nameservers were processed, return error
            if (empty($result)) {
                return [
                    'error' => 'Could not process nameserver information',
                ];
            }

            return $result;

        } catch (SpaceshipException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get a domain registrar lock
     *
     * @param array $params WHMCS parameters
     * @return array|string WHMCS response array
     */

    public function getRegistrarLock($params)
    {
        try {
            $response = $this->getDomainInfo($params);

            return $response->isTransferLocked() ? 'locked' : 'unlocked';
        } catch (SpaceshipException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Save nameservers
     *
     * @param array $params WHMCS parameters
     * @return array WHMCS response array
     */
    public function saveNameservers(array $params): array
    {
        try {
            $nameservers = [];
            for ($i = 1; $i <= 5; $i++) {
                if (!empty($params["ns$i"])) {
                    $nameservers[] = $params["ns$i"];
                }
            }

            $nsParams = NameserverParams::make()
                ->setProvider(NameserverProviders::CUSTOM)
                ->setHosts($nameservers);

            $this->api->updateNameserver(
                $params['sld'] . '.' . $params['tld'],
                $nsParams
            );

            return [
                'success' => true,
            ];
        } catch (SpaceshipException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Contact Details for WHMCS
     *
     * @param array $params WHMCS parameters
     * @return array Contact details in WHMCS format
     */
    public function getContactDetails(array $params): array
    {
        try {
            // First get domain info to get contact IDs
            $domainInfo = $this->api->domain($params['sld'] . '.' . $params['tld']);

            $contacts = [
                'Registrant' => [],
                'Admin' => [],
                'Technical' => [],
                'Billing' => [],
            ];

            // Get Registrant Contact
            if (!empty($domainInfo->getRegistrantId())) {
                $registrant = $this->api->contact($domainInfo->getRegistrantId());
                $contacts['Registrant'] = $this->formatContactForWHMCS($registrant);
            }

            // Get Admin Contact
            if (!empty($domainInfo->getAdminContactId())) {
                $admin = $this->api->contact($domainInfo->getAdminContactId());
                $contacts['Admin'] = $this->formatContactForWHMCS($admin);
            }

            // Get Technical Contact
            if (!empty($domainInfo->getTechContactId())) {
                $technical = $this->api->contact($domainInfo->getTechContactId());
                $contacts['Technical'] = $this->formatContactForWHMCS($technical);
            }

            // Get Billing Contact
            if (!empty($domainInfo->getBillingContactId())) {
                $billing = $this->api->contact($domainInfo->getBillingContactId());
                $contacts['Billing'] = $this->formatContactForWHMCS($billing);
            }

            return $contacts;

        } catch (SpaceshipException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format contact data for WHMCS
     *
     * @param ContactResponse $contact Contact data from API
     * @return array Formatted contact data
     */
    private function formatContactForWHMCS(ContactResponse $contact): array
    {
        return [
            'First Name' => $contact->firstName() ?? '',
            'Last Name' => $contact->lastName() ?? '',
            'Company Name' => $contact->organization() ?? '',
            'Email Address' => $contact->email() ?? '',
            'Address 1' => $contact->address1() ?? '',
            'Address 2' => $contact->address2() ?? '',
            'City' => $contact->city() ?? '',
            'State' => $contact->state() ?? '',
            'Postcode' => $contact->postalCode() ?? '',
            'Country' => $contact->countryCode() ?? '',
            'Phone Number' => $this->formatPhoneNumber($contact->phone() ?? ''),
            'Fax Number' => $this->formatPhoneNumber($contact->fax() ?? ''),
        ];
    }

    /**
     * Format phone number for WHMCS
     *
     * @param string $phone Phone number from API
     * @return string Formatted phone number
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove any formatting and keep only numbers and plus sign
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Ensure it starts with + if it's an international number
        if (!empty($phone) && $phone[0] !== '+') {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Get EPP Code
     *
     * @param array $params WHMCS parameters
     * @return array WHMCS response array
     */
    public function getEPPCode(array $params): array
    {
        try {
            $response = $this->api->authCode(
                $params['sld'] . '.' . $params['tld']
            );

            return [
                'success' => true,
                'eppcode' => $response->authCode() ?? '',
            ];
        } catch (SpaceshipException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Toggle ID Protection
     *
     * @param array $params WHMCS parameters
     * @return array WHMCS response array
     */
    public function toggleIdProtection(array $params): array
    {
        try {
            $level = $params['protectenable'] ?
            PrivacyLevel::HIGH :
            PrivacyLevel::PUBLIC;

            $protectParams = PrivacyProtectionParams::make()
                ->setPrivacyLevel($level);

            $this->api->updatePrivacyProtection(
                $params['sld'] . '.' . $params['tld'],
                $protectParams
            );

            return [
                'success' => true,
            ];
        } catch (SpaceshipException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Toggle Transfer Lock
     *
     * @param array $params WHMCS parameters
     * @return array WHMCS response array
     */
    public function toggleRegistrarLock(array $params): array
    {
        try {
            $lockParams = TransferLockParams::make();

            $params['lockenabled'] === 'locked' ? $lockParams->lock() : $lockParams->unlock();

            $this->api->updateTransferLock(
                $params['sld'] . '.' . $params['tld'],
                $lockParams
            );

            return ['success' => true];
        } catch (SpaceshipException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Update Contact Details
     *
     * @param array $params WHMCS parameters
     * @return array WHMCS response array
     */
    public function updateContactDetails(array $params): array
    {
        try {
            // Create new contact with updated details
            $contactParams = CreateContactParams::make()
                ->setFirstName($params['firstname'])
                ->setLastName($params['lastname'])
                ->setEmail($params['email'])
                ->setAddress1($params['address1'])
                ->setAddress2($params['address2'] ?? '')
                ->setCity($params['city'])
                ->setStateProvince($params['state'])
                ->setCountryCode($params['countrycode'])
                ->setPostalCode($params['postcode'])
                ->setPhone($params['phonenumberformatted'])
                ->setOrganization($params['companyname'] ?? '');

            $contact = $this->api->createContact($contactParams);

            // Update domain contacts maintaining existing roles where no new details provided
            $updateParams = UpdateContactParams::make()
                ->setRegistrant($contact->contactId())
                ->setAdmin($contact->contactId())
                ->setTech($contact->contactId())
                ->setBilling($contact->contactId());

            $this->api->updateContact(
                $params['sld'] . '.' . $params['tld'],
                $updateParams
            );

            return [
                'success' => true,
            ];
        } catch (SpaceshipException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync Domain Status & Expiration Date
     *
     * @param array $params WHMCS parameters
     * @return array WHMCS response array
     */
    public function syncDomainStatus(array $params): array
    {
        try {
            $response = $this->getDomainInfo($params);

            return [
                'expirydate' => $response->expirationDate() ?? '',
                'active' => $response->isActive() ?? true,
                'expired' => $response->isExpired() ?? false,
                'transferredAway' => false,
            ];
        } catch (SpaceshipException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
