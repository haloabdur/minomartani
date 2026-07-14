<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Shield\Authentication\Passwords;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    /**
     * Overrides Shield's default login rules (which hard-require a valid
     * "email" field) so a single login box can accept either an email or
     * a username - exactly one of the two is required, and only the email
     * field is format-checked. Populated in the constructor since the
     * password rule needs Passwords::getMaxLengthRule(), which isn't a
     * compile-time constant.
     *
     * @var array<string, array<string, list<string>|string>>
     */
    public array $login = [];

    public function __construct()
    {
        parent::__construct();

        $this->login = [
            'email' => [
                'label' => 'Auth.email',
                'rules' => ['permit_empty', 'valid_email', 'required_without[username]'],
            ],
            'username' => [
                'label' => 'Auth.username',
                'rules' => ['permit_empty', 'required_without[email]'],
            ],
            'password' => [
                'label' => 'Auth.password',
                'rules' => ['required', Passwords::getMaxLengthRule()],
            ],
        ];
    }
}
