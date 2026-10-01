<?php

/*
|--------------------------------------------------------------------------
| Conteúdo da landing page
|--------------------------------------------------------------------------
| Todo o texto da página inicial mora aqui. Para mudar copy, módulos,
| formatos ou FAQ, edite este arquivo — as views só fazem o loop.
*/

return [

    'nav' => [
        ['label' => 'Trilha', 'href' => '#trilha'],
        ['label' => 'Formatos', 'href' => '#formatos'],
        ['label' => 'FAQ', 'href' => '#faq'],
    ],

    'hero' => [
        'title' => 'Saia do nível júnior usando IA',
        'accent' => 'do jeito certo',
        'text' => 'Consultoria em IA aplicada para desenvolvedores júnior. Aprenda a usar agentes, skills e Claude Code no seu dia a dia, entregue mais rápido e se destaque no time desde o primeiro projeto.',
        'gallery' => [
            ['src' => 'images/hero-chuva.jpg', 'alt' => 'Ilustração cyberpunk de personagem olhando para a chuva na cidade'],
            ['src' => 'images/hero-olhar.jpg', 'alt' => 'Ilustração em estilo mangá de um olhar determinado'],
            ['src' => 'images/hero-ciborgue.jpg', 'alt' => 'Ilustração de personagem ciborgue'],
        ],
    ],

    'modules' => [
        [
            'code' => '01',
            'title' => 'Agentes de IA',
            'text' => 'Entenda o loop de um agente — contexto, ferramentas e verificação — e aprenda a delegar tarefas reais sem perder o controle do código.',
        ],
        [
            'code' => '02',
            'title' => 'Skills',
            'text' => 'Empacote o jeito certo de fazer as coisas em skills reutilizáveis: revisão de PR, testes, padrões do time. Escreva uma vez, use sempre.',
        ],
        [
            'code' => '03',
            'title' => 'Claude Code no fluxo',
            'text' => 'Do terminal ao pull request: planejar, implementar, testar e revisar com IA dentro do seu repositório, do jeito que times maduros trabalham.',
        ],
        [
            'code' => '04',
            'title' => 'Automações e MCP',
            'text' => 'Conecte a IA às ferramentas que você já usa e automatize o trabalho repetitivo — com hooks, MCP e rotinas que rodam sozinhas.',
        ],
    ],

    'terminal' => [
        ['type' => 'cmd', 'text' => 'claude'],
        ['type' => 'cmd', 'prompt' => '>', 'text' => '/revisar-pr 42'],
        ['type' => 'out', 'text' => 'Carregando skill .claude/skills/revisar-pr/SKILL.md'],
        ['type' => 'out', 'text' => 'Subagente "explorador" mapeando os arquivos alterados…'],
        ['type' => 'out', 'text' => 'Rodando a suíte de testes antes de opinar'],
        ['type' => 'ok', 'text' => 'Review pronto: o que corrigir, por quê e onde.'],
    ],

    'audience' => [
        'Estudantes de Ciência da Computação e Análise de Sistemas',
        'Estagiários e devs júnior no primeiro time',
        'Quem já usa chat de IA mas trava em projetos de verdade',
        'Curiosos por tecnologia que querem construir, não só assistir',
    ],

    /*
    | 'price' => null exibe "Sob consulta". Preencha (ex.: 'R$ 000') quando
    | os valores estiverem definidos.
    */
    'plans' => [
        [
            'slug' => 'sessao',
            'name' => 'Sessão avulsa',
            'tagline' => 'Para destravar um problema específico.',
            'price' => null,
            'features' => ['1 encontro ao vivo', 'Diagnóstico do seu fluxo atual', 'Plano de próximos passos'],
            'featured' => false,
        ],
        [
            'slug' => 'mentoria',
            'name' => 'Mentoria individual',
            'tagline' => 'A trilha completa, no seu ritmo e no seu projeto.',
            'price' => null,
            'features' => ['Encontros semanais ao vivo', 'Os 4 módulos da trilha', 'Revisão do seu código e das suas skills', 'Suporte entre os encontros'],
            'featured' => true,
        ],
        [
            'slug' => 'squad',
            'name' => 'Squad',
            'tagline' => 'Para grupos de estudo, turmas e times pequenos.',
            'price' => null,
            'features' => ['Encontros em grupo', 'Projeto prático em conjunto', 'Skills compartilhadas pelo grupo'],
            'featured' => false,
        ],
    ],

    'faq' => [
        [
            'q' => 'Preciso já saber programar?',
            'a' => 'Sim, o básico. A consultoria é para quem já escreve código — na faculdade, no estágio ou em projetos pessoais — e quer aprender a trabalhar com IA de forma profissional.',
        ],
        [
            'q' => 'Usar IA não vai me impedir de aprender de verdade?',
            'a' => 'Só se você usar no piloto automático. O método é justamente o contrário: você aprende a ler, questionar e verificar o que o agente produz, e isso acelera o seu aprendizado em vez de substituí-lo.',
        ],
        [
            'q' => 'Quais ferramentas vou precisar?',
            'a' => 'Um computador, um editor, Git e acesso ao Claude Code. Na primeira conversa a gente alinha o setup e o que faz sentido para o seu momento.',
        ],
        [
            'q' => 'Em qual linguagem ou stack?',
            'a' => 'Na sua. Agentes e skills funcionam com qualquer stack; trabalhamos em cima do projeto que você já tem.',
        ],
        [
            'q' => 'Como funciona a primeira conversa?',
            'a' => 'É um bate-papo rápido e sem compromisso para entender onde você está, aonde quer chegar e qual formato combina com você.',
        ],
    ],

    'levels' => [
        'estudante' => 'Estudante',
        'estagiario' => 'Estagiário(a)',
        'junior' => 'Dev júnior',
        'outro' => 'Outro',
    ],

];
