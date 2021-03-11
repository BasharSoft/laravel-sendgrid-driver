<?php
namespace Sichikawa\LaravelSendgridDriver\Helpers;

/**
 * Description of MailSettings
 *
 * @author SherifMedhat <sherif.medhat@basharsoft.com>
 */
class MailParams
{

    /**
     * Time to send the email at
     * 
     * @var integer|null 
     */
    protected $sendAt = null;

    /**
     * Email categories
     * 
     * @var string[] 
     */
    protected $categories = [];

    /**
     * Custom Args is a data that you want to pass with the email, and want to recieve it with the email status
     *
     * @var string[]
     */
    protected $customArgs = [];

    /**
     * Get the send at value
     * 
     * @return integer|null
     */
    public function getSendAt()
    {
        return $this->sendAt;
    }

    /**
     * set the send at value
     * 
     * @param integer|null $sendTime    The send at time in unix timestamp
     * 
     * @return $this
     */
    public function setSendAt($sendTime)
    {
        $this->sendAt = $sendTime;

        return $this;
    }

    /**
     * Get the categories list
     * 
     * @return string[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Add a new category to the list
     * 
     * @param string $category  Category value
     * 
     * @return $this
     */
    public function addCategory($category)
    {
        if (!empty($category) && !in_array($category, $this->categories)) {
            $this->categories[] = $category;
        }

        return $this;
    }

    /**
     * Set the categories list
     * 
     * @param array $categories Categories list
     * 
     * @return $this
     */
    public function setCategories(array $categories = [])
    {
        $this->categories = [];

        foreach ($categories as $category) {
            $this->addCategory($category);
        }

        return $this;
    }

    /**
     * Add a new custom argument to the list
     *
     * @param string $customArg  Custom argument
     * @param string $value      Custom argument value
     *
     * @return $this
     */
    public function addCustomArg($customArg, $value)
    {
        if (!empty($customArg) && !isset($this->customArgs[$customArg])) {
            $this->customArgs[$customArg] = $value;
        }

        return $this;
    }

    /**
     * Get the CustomArgs list
     *
     * @return string[]
     */
    public function getCustomArgs()
    {
        return $this->customArgs;
    }
}
