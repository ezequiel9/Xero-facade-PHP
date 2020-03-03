<?php

namespace App\Facades;

use App\User;
use function GuzzleHttp\Psr7\str;
use XeroPHP\Application\PrivateApplication;
use XeroPHP\Models\Accounting\Account;
use XeroPHP\Models\Accounting\Address;
use XeroPHP\Models\Accounting\Contact;
use XeroPHP\Models\Accounting\Invoice;
use XeroPHP\Models\Accounting\Payment;
use XeroPHP\Models\Accounting\Phone;
use Illuminate\Support\Facades\Mail;
use XeroPHP\Remote\Exception\NotFoundException;

/**
 * Class XeroFacade
 * @package App\Facades
 */
class XeroFacade
{

    private $xero;
    private $config;

    /**
     * XeroFacade constructor.
     * @param $config
     */
    public function __construct($config) # $xero contains Xero config from DB
    {
        $this->config = $config;
        $this->xero = new PrivateApplication($this->getXeroConfig());
    }

    /**
     * Get the registered name of the component.
     * @return \XeroPHP\Remote\Collection
     * @throws \XeroPHP\Remote\Exception
     */
    public function getAllContacts($page=1)
    {
        # get contacts from XERO
        if($page){
            return $this->xero->load(Contact::class)->where('EmailAddress', '!=', null)->page($page)->execute();
        }else{
            return $this->xero->load(Contact::class)->execute();
        }
    }

    /**
     * Get the ContactID by Email registered
     * @param $email
     * @return bool
     * @throws \XeroPHP\Remote\Exception
     */
    public function getContact($email)
    {
    	$contact = $this->xero->load(Contact::class)
            ->where('EmailAddress', trim(strtolower($email)))
            ->execute();
        if(sizeof($contact) > 0){
            return $contact[0]->getContactID();
        }
		return false;
    }


    /**
     * Get the ContactID by Email registered
     * @param $email
     * @return bool
     * @throws \XeroPHP\Remote\Exception
     */
    public function getContactByName($name)
    {
    	$contact = $this->xero->load(Contact::class)
            ->where('Name', $name)
            ->orWhere('Name.ToLower()', strtolower($name))
            ->execute();
        if(sizeof($contact) > 0){
            return $contact[0]->getContactID();
        }
		return false;
    }


    /**
     * Data format
     *
     *   $data = [
     *       'ContactID' => '123213-123123-123123-123123  || null',
     *       'EmailAddress' => '12312',
     *       'Name' => '12312',
     *       'FirstName' => '12312',
     *       'LastName' => '12312',
     *       'Addresses' => [
     *           "City" => '12312',
     *           "Region" => '12312',
     *           "PostalCode" => '12312',
     *           "Country" => '12312',
     *           "AddressLine1" => '12312',
     *       ],
     *       'Phones' => 'xxxxx'
     *   ];
     *
     * @param $data
     * @return bool
     * @throws \XeroPHP\Remote\Exception
     */
    public function createContact($data)
    {
        /**
         * Set Address
         */
        $address = new Address($this->xero);
        $address->setAddressType(Address::ADDRESS_TYPE_STREET)
            ->setAddressLine1($data['Addresses']['AddressLine1'])
            ->setCity($data['Addresses']['City'])
            ->setCountry($data['Addresses']['Country'])
            ->setPostalCode($data['Addresses']['PostalCode'])
            ->setRegion($data['Addresses']['Region']);

        /**
         * Set Phone
         */
        $phone = new Phone($this->xero);
        $phone->setPhoneType(Phone::PHONE_TYPE_DEFAULT)
            ->setPhoneNumber($data['Phones']);

        /**
         * Create Contact
         */
        $contact = new Contact($this->xero);
        $xero_response = $contact->setName($data['Name'])
            ->setFirstName($data['FirstName'])
            ->setLastName($data['LastName'])
            ->setEmailAddress($data['EmailAddress'])
            ->addAddress($address)
            ->addPhone($phone)
            ->save();
        return $xero_response->getElements()[0];
    }


    /**
     * @param $data
     * @return mixed
     * @throws \XeroPHP\Exception
     * @throws \XeroPHP\Remote\Exception\NotFoundException
     */
    public function updateContact($data)
    {
        /**
         * Set Address
         */
        $address = new Address($this->xero);
        $address->setAddressType(Address::ADDRESS_TYPE_STREET)
            ->setAddressLine1($data['Addresses']['AddressLine1'])
            ->setCity($data['Addresses']['City'])
            ->setCountry($data['Addresses']['Country'])
            ->setPostalCode($data['Addresses']['PostalCode'])
            ->setRegion($data['Addresses']['Region']);

        /**
         * Set Phone
         */
        $phone = new Phone($this->xero);
        $phone->setPhoneType(Phone::PHONE_TYPE_DEFAULT)
            ->setPhoneNumber($data['Phones']);

        /**
         * Create Contact
         */
        $contact = $this->xero->loadByGUID(Contact::class, $data['ContactID']);
        $xero_response = $contact->setFirstName($data['FirstName'])
            ->setLastName($data['LastName'])
            ->addAddress($address)
            ->addPhone($phone)
            ->save();
        return $xero_response->getElements();

    }

    /**
     * Get all Accounts for this current config
     *
     * @return \XeroPHP\Remote\Collection
     * @throws \XeroPHP\Remote\Exception
     */
    public function getAccounts()
    {
        return $this->xero->load(Account::class)->execute();
    }

    /**
     * Get all Accounts for this current config by code
     *
     * @param $code
     * @return \XeroPHP\Remote\Collection
     * @throws \XeroPHP\Remote\Exception
     */
    public function getAccountByCode($code)
    {
        return $this->xero->load(Account::class)->where('Code', (string) $code)->execute();
    }

    /**
     * Get all Accounts for this current config
     *
     * @return \XeroPHP\Remote\Collection
     * @throws \XeroPHP\Remote\Exception
     */
    public function getAccountByName()
    {
        return $this->xero->load(Account::class)->execute();
    }



    /**
     * Get all payments
     *
     * @return \XeroPHP\Remote\Collection
     * @throws \XeroPHP\Remote\Exception
     */
    public function getPayments()
    {
        return $this->xero->load(Payment::class)->orderBy('Date', 'DESC')->execute();
    }


    /**
     * Create invoice
     *
     *
     * @param $data
     * @return mixed
     * @throws \XeroPHP\Remote\Exception
     */
    public function createInvoice($data)
    {

        //looping user.
        //$xero_id = $data['user']->xeroRemoteId;
        $xero_id = null;
        foreach ($data['user']->xeroRemoteId as $user_xero) {
            if($user_xero){
                $xero_id =  $user_xero->pivot->remote_xero_id;
            }
        }


        if(!empty($xero_id)){
            $contact = $this->xero->load(Contact::class)
                ->where('ContactID=GUID("' . $xero_id . '")')
                ->execute();
            if(is_null($contact) || sizeof($contact) == 0){
                $xero_id = null;
            }else{
                $contact = $contact[0];
            }
        }

        if(empty($xero_id)){
            # empty xero id on current user
            $xero_id = $this->getContactByName($data['user']->name);
            if(!$xero_id){
                $data_new_contact = [
                    'ContactID' => null,
                    'EmailAddress' => $data['user']->email,
                    'Name' => $data['user']->name,
                    'FirstName' => $data['user']->first_name,
                    'LastName' => $data['user']->last_name,
                    'Addresses' => [
                        "AddressType" => "STREET",
                        "City" => $data['user']->city,
                        "Region" => $data['user']->state,
                        "PostalCode" => $data['user']->zip_code,
                        "Country" => $data['user']->country,
                        "AddressLine1" => $data['user']->st_number . ' ' . $data['user']->st_address,
                    ],
                    'Phones' => $data['user']->phone
                ];            
                $xero_id = $this->createContact($data_new_contact);
                $xero_id = $xero_id['ContactID'];

            }

            $contact = $this->xero->load(Contact::class)
                ->where('ContactID=GUID("' . $xero_id . '")')
                ->execute();
            $contact = $contact[0];
            User::find($data['user']->id)->xeroRemoteId()->wherePivot('xero_account_id', $this->config->id)->detach();
            User::find($data['user']->id)->xeroRemoteId()->attach($this->config->id, ['remote_xero_id' => $xero_id]);
        }

        $xero_invoice = new Invoice($this->xero);
        $xero_invoice->setType($data['Type']);
        $xero_invoice->setContact($contact);
        $xero_invoice->setReference($data['Reference']);
        $xero_invoice->setLineAmountType($data['AmountType']);
        $xero_invoice->setInvoiceNumber($data['InvoiceNumber']);
        $xero_invoice->setStatus($data['Status']);
        $xero_invoice->setDueDate(\DateTime::createFromFormat('Y-m-d', $data['DueDate'] ));
        foreach($data['LineItems'] as $line_item){
            $xero_line_item = new Invoice\LineItem($this->xero);
            $xero_line_item->setQuantity($line_item['Quantity']);
            $xero_line_item->setDescription($line_item['Description']);
            $xero_line_item->setUnitAmount($line_item['UnitAmount']);
            $xero_line_item->setTaxType($line_item['TaxType']);
            $xero_line_item->setAccountCode($line_item['AccountCode']);
            $xero_invoice->addLineItem($xero_line_item);
        }

        return $xero_invoice->save()->getElements()[0];
    }


    /**
     * @param $invoice_number
     * @return bool
     * @throws \XeroPHP\Remote\Exception
     */
    public function getInvoicePdf($invoice_number)
    {
        # get invoices from XERO
        $pdf_invoice = $this->getInvoice($invoice_number);
        if(!$pdf_invoice){
            echo 'Invoice not found';
            return false;
        }
        header('Content-Disposition: attachment; filename="invoice.pdf"');
        header("Content-type: application/pdf");
        header("Content-Transfer-Encoding: binary");
        echo $pdf_invoice->getPDF();
    }


    /**
     * @param $invoice_number
     * @return mixed
     * @throws \XeroPHP\Remote\Exception
     */
    public function getInvoice($invoice_number)
    {
        $invoice = $this->xero->load(Invoice::class)->where('InvoiceNumber', $invoice_number)->execute();
        if(empty($invoice[0])){
            return false;
        }
        return $invoice[0];
    }


    /**
     * Get config
     *
     * @return array
     */
    public function getXeroConfig()
    {
        return [
            'xero' => [
                // API versions can be overridden if necessary for some reason.
                //'core_version'     => '2.0',
                //'payroll_version'  => '1.0',
                //'file_version'     => '1.0'
            ],
            'oauth' => [
                'callback'          => $this->config->xero_callback,
                'consumer_key'      => $this->config->xero_consumer_key,
                'consumer_secret'   => $this->config->xero_consumer_secret,
                //If you have issues passing the Authorization header, you can set it to append to the query string
                //'signature_location'    => \XeroPHP\Remote\OAuth\Client::SIGN_LOCATION_QUERY
                //For certs on disk or a string - allows anything that is valid with openssl_pkey_get_(private|public)
                'rsa_private_key'  => env('XERO_CERT_PATH', 'file:///var/www/sport/html/cert/privatekey.pem')
            ],
            //These are raw curl options.  I didn't see the need to obfuscate these through methods
            'curl' => [
                CURLOPT_USERAGENT   => 'Xero Sync System SZ',
            ]
        ];

    }




}
