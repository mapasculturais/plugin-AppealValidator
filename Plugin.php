<?php

namespace AppealValidator;

require_once __DIR__ . "/../AbstractValidator/AbstractValidator.php";

use MapasCulturais\i;
use MapasCulturais\App;
use MapasCulturais\Entities\Registration;
use StreamlinedOpportunity\Plugin as StreamlinedOpportunity;


class Plugin extends \AbstractValidator\AbstractValidator
{
    /**
     * @property-read String $sln_register id_register configurado para o plugin StreamLinedOpportunit
     */
    protected $sln_register;

    protected static $instance = null;

    function __construct(array $config = [])
    {

        self::$instance =  $this;

        $config += [
            'consolidacao_requer_homologacao' => false,
            'consolidacao_requer_validacoes' => false,

            // se true, só exporta as inscrições pendentes que já tenham alguma avaliação
            'exportador_requer_homologacao' => false,

            // se true, só exporta as inscrições 
            'exportador_requer_validacao' => [],

            'result_homologada' => i::__('homologada'),
            'obs_homologada' => i::__('Recurso deferido'),

            'result_analise' => i::__('recurso em análise'),
            'obs_analise' => i::__('Recurso recebido e em análise'),

            'result_selecionada' => i::__('selecionada por recurso'),
            'obs_selecionada' => i::__('Recurso deferido'),

            'result_invalida' => i::__('2'),
            'obs_invalida' => i::__('Recurso negado'),

            'result_nao_selecionada' => i::__('3'),
            'obs_nao_selecionada' => i::__('Recurso indeferido'),

            'result_suplente' => i::__('8'),
            'obs_suplente' => i::__('Recurso: inscrição suplente'),

        ];

        $this->_config = $config;

        parent::__construct($config);

    }

    function _init()
    {
        $app = App::i();

        $plugin = $this;

        // Exibe botões para upload e download
        $app->hook('template(opportunity.<<single|edit>>.sidebar-right):end', function () use ($plugin) {
            $opportunity = $this->controller->requestedEntity;
            $is_opportunity_managed_handler = $plugin->config['is_opportunity_managed_handler']($opportunity);
            
            if ($is_opportunity_managed_handler && $opportunity->canUser('@control')) {
                $slo_instance = StreamlinedOpportunity::getInstanceByOpportunityId($opportunity->id);
                $this->part('validador-recursos/validador-uploads', ['entity' => $opportunity, 'slo_instance' => $slo_instance, 'plugin' => $plugin]);
            }
        });

        /**
         * @TODO: implementar para metodo de avaliação documental
         */
        $app->hook('entity(Registration).consolidateResult', function (&$result, $caller) use ($plugin, $app) {
            if ($recurso = $app->repo('RegistrationEvaluation')->findOneBy(['registration' => $caller->registration, 'user' => $plugin->getUser()])) {
                $result = $caller->result;
            }
        }, 100000);

        parent::_init();
    }

    function register()
    {
        $app = App::i();

        $this->registerOpportunityMetadata($this->prefix("processed_files"), [
            'label' => 'Arquivos do Validador Financeiro Processados',
            'type' => 'json',
            'private' => true,
            'default_value' => '{}'
        ]);

        $this->registerRegistrationMetadata($this->prefix("filename"), [
            'label' => 'Nome do arquivo de retorno do validador financeiro',
            'type' => 'json',
            'private' => true,
            'default_value' => '[]'
        ]);

        $this->registerRegistrationMetadata($this->prefix("raw"), [
            'label' => 'Validador Financeiro raw data (csv row)',
            'type' => 'json',
            'private' => true,
            'default_value' => '{}'
        ]);

        $this->registerRegistrationMetadata($this->prefix("processed"), [
            'label' => 'Validador Financeiro processed data',
            'type' => 'json',
            'private' => true,
            'default_value' => '{}'
        ]);

        $file_group_definition = new \MapasCulturais\Definitions\FileGroup($this->getSlug(), ['^text/csv$'], 'O arquivo enviado não é um csv.', false, null, true);
        $app->registerFileGroup('opportunity', $file_group_definition);

        parent::register();
    }


    public static function getInstance()
    {
        return  self::$instance;
    }

    function getName(): string
    {
        return 'Validador de Recursos';
    }

    function getSlug(): string
    {
        return "validador_recurso";
    }

    function getControllerClassname(): string
    {
        return Controller::class;
    }

    function isRegistrationEligible(Registration $registration): bool
    {
        return true;
    }

    function prefix($value)
    {
        return $this->getSlug() . "_" . $value;
    }
}
