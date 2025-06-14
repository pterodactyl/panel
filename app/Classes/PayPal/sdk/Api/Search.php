<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class Search
 *
 * Invoice search parameters.
 *
 * @package PayPal\Api
 *
 * @property string email
 * @property string recipient_first_name
 * @property string recipient_last_name
 * @property string recipient_business_name
 * @property string number
 * @property string status
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency lower_total_amount
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency upper_total_amount
 * @property string start_invoice_date
 * @property string end_invoice_date
 * @property string start_due_date
 * @property string end_due_date
 * @property string start_payment_date
 * @property string end_payment_date
 * @property string start_creation_date
 * @property string end_creation_date
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\number page
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\number page_size
 * @property bool total_count_required
 */
class Search extends PayPalModel
{
    /**
     * Initial letters of the email address.
     *
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Initial letters of the email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Initial letters of the recipient's first name.
     *
     * @param string $recipient_first_name
     *
     * @return $this
     */
    public function setRecipientFirstName($recipient_first_name)
    {
        $this->recipient_first_name = $recipient_first_name;
        return $this;
    }

    /**
     * Initial letters of the recipient's first name.
     *
     * @return string
     */
    public function getRecipientFirstName()
    {
        return $this->recipient_first_name;
    }

    /**
     * Initial letters of the recipient's last name.
     *
     * @param string $recipient_last_name
     *
     * @return $this
     */
    public function setRecipientLastName($recipient_last_name)
    {
        $this->recipient_last_name = $recipient_last_name;
        return $this;
    }

    /**
     * Initial letters of the recipient's last name.
     *
     * @return string
     */
    public function getRecipientLastName()
    {
        return $this->recipient_last_name;
    }

    /**
     * Initial letters of the recipient's business name.
     *
     * @param string $recipient_business_name
     *
     * @return $this
     */
    public function setRecipientBusinessName($recipient_business_name)
    {
        $this->recipient_business_name = $recipient_business_name;
        return $this;
    }

    /**
     * Initial letters of the recipient's business name.
     *
     * @return string
     */
    public function getRecipientBusinessName()
    {
        return $this->recipient_business_name;
    }

    /**
     * The invoice number that appears on the invoice.
     *
     * @param string $number
     *
     * @return $this
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * The invoice number that appears on the invoice.
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Status of the invoice.
     * Valid Values: ["DRAFT", "SENT", "PAID", "MARKED_AS_PAID", "CANCELLED", "REFUNDED", "PARTIALLY_REFUNDED", "MARKED_AS_REFUNDED"]
     *
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Status of the invoice.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Lower limit of total amount.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $lower_total_amount
     *
     * @return $this
     */
    public function setLowerTotalAmount($lower_total_amount)
    {
        $this->lower_total_amount = $lower_total_amount;
        return $this;
    }

    /**
     * Lower limit of total amount.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getLowerTotalAmount()
    {
        return $this->lower_total_amount;
    }

    /**
     * Upper limit of total amount.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $upper_total_amount
     *
     * @return $this
     */
    public function setUpperTotalAmount($upper_total_amount)
    {
        $this->upper_total_amount = $upper_total_amount;
        return $this;
    }

    /**
     * Upper limit of total amount.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getUpperTotalAmount()
    {
        return $this->upper_total_amount;
    }

    /**
     * Start invoice date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $start_invoice_date
     *
     * @return $this
     */
    public function setStartInvoiceDate($start_invoice_date)
    {
        $this->start_invoice_date = $start_invoice_date;
        return $this;
    }

    /**
     * Start invoice date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getStartInvoiceDate()
    {
        return $this->start_invoice_date;
    }

    /**
     * End invoice date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $end_invoice_date
     *
     * @return $this
     */
    public function setEndInvoiceDate($end_invoice_date)
    {
        $this->end_invoice_date = $end_invoice_date;
        return $this;
    }

    /**
     * End invoice date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getEndInvoiceDate()
    {
        return $this->end_invoice_date;
    }

    /**
     * Start invoice due date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $start_due_date
     *
     * @return $this
     */
    public function setStartDueDate($start_due_date)
    {
        $this->start_due_date = $start_due_date;
        return $this;
    }

    /**
     * Start invoice due date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getStartDueDate()
    {
        return $this->start_due_date;
    }

    /**
     * End invoice due date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $end_due_date
     *
     * @return $this
     */
    public function setEndDueDate($end_due_date)
    {
        $this->end_due_date = $end_due_date;
        return $this;
    }

    /**
     * End invoice due date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getEndDueDate()
    {
        return $this->end_due_date;
    }

    /**
     * Start invoice payment date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $start_payment_date
     *
     * @return $this
     */
    public function setStartPaymentDate($start_payment_date)
    {
        $this->start_payment_date = $start_payment_date;
        return $this;
    }

    /**
     * Start invoice payment date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getStartPaymentDate()
    {
        return $this->start_payment_date;
    }

    /**
     * End invoice payment date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $end_payment_date
     *
     * @return $this
     */
    public function setEndPaymentDate($end_payment_date)
    {
        $this->end_payment_date = $end_payment_date;
        return $this;
    }

    /**
     * End invoice payment date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getEndPaymentDate()
    {
        return $this->end_payment_date;
    }

    /**
     * Start invoice creation date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $start_creation_date
     *
     * @return $this
     */
    public function setStartCreationDate($start_creation_date)
    {
        $this->start_creation_date = $start_creation_date;
        return $this;
    }

    /**
     * Start invoice creation date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getStartCreationDate()
    {
        return $this->start_creation_date;
    }

    /**
     * End invoice creation date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $end_creation_date
     *
     * @return $this
     */
    public function setEndCreationDate($end_creation_date)
    {
        $this->end_creation_date = $end_creation_date;
        return $this;
    }

    /**
     * End invoice creation date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getEndCreationDate()
    {
        return $this->end_creation_date;
    }

    /**
     * Offset of the search results.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\number $page
     *
     * @return $this
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * Offset of the search results.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\number
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Page size of the search results.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\number $page_size
     *
     * @return $this
     */
    public function setPageSize($page_size)
    {
        $this->page_size = $page_size;
        return $this;
    }

    /**
     * Page size of the search results.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\number
     */
    public function getPageSize()
    {
        return $this->page_size;
    }

    /**
     * A flag indicating whether total count is required in the response.
     *
     * @param bool $total_count_required
     *
     * @return $this
     */
    public function setTotalCountRequired($total_count_required)
    {
        $this->total_count_required = $total_count_required;
        return $this;
    }

    /**
     * A flag indicating whether total count is required in the response.
     *
     * @return bool
     */
    public function getTotalCountRequired()
    {
        return $this->total_count_required;
    }

}
