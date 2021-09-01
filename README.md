# plugin-AbstractValidator
Plugin que agrega funcionalidade de validação de recurso

## Exemplo de Configuração

```
    "AppealValidator" => [
        "namespace" => "AppealValidator",
        "config" => [
            "is_opportunity_managed_handler" => function ($opportunity) {
                return ($opportunity->id == 42);
            },
        ]
    ],
```