# 🔍 Extrair CEPs - Correios
Uma ferramenta web moderna e eficiente para extrair CEPs por bairro diretamente da base oficial dos Correios. Ideal para e-commerces que precisam configurar fretes por região, esta solução automatiza e simplifica o processo de obtenção de CEPs de forma organizada e confiável.

## ✨ Características

- 🚀 **Extração Completa**: Busca todos os CEPs de um bairro, não apenas os primeiros 50
- 🎯 **Interface Moderna**: Design responsivo e intuitivo
- 🔒 **CAPTCHA**: Resolução manual do CAPTCHA dos Correios
- 📁 **Organização Inteligente**: Estrutura de pastas por estado/cidade
- 📊 **Logs Centralizados**: Sistema de debug e monitoramento
- ⚡ **Paginação Automática**: Percorre todas as páginas de resultados
- 💾 **Download Direto**: Salva arquivos TXT organizados

## 🛠️ Tecnologias

- **Backend**: PHP 7.4+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Integração**: API dos Correios via cURL

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- Extensão cURL habilitada
- Servidor web (Apache/Nginx)
- Navegador moderno

## 🚀 Instalação

1. **Clone o repositório:**
```bash
git clone https://github.com/gabrielfilippi/extrair-ceps-correios.git
cd extrair-ceps-correios
```

2. **Configure o servidor web:**
   - Coloque os arquivos na pasta do seu servidor web
   - Para XAMPP: `C:\xampp\htdocs\extrair-ceps-correios\`
   - Para WAMP: `C:\wamp64\www\extrair-ceps-correios\`

3. **Acesse no navegador:**
```
http://localhost/extrair-ceps-correios/
```

## 📖 Como Usar

1. **Selecione o Estado (UF)**
2. **Digite o nome da Cidade**
3. **Digite o nome do Bairro**
4. **Clique em "Carregar CAPTCHA"**
5. **Digite o texto do CAPTCHA**
6. **Clique em "Buscar CEPs"**
7. **Aguarde a extração completa**
8. **Baixe o arquivo TXT com todos os CEPs**

## 📁 Estrutura de Arquivos

```
extrair-ceps-correios/
├── index.php              # Backend PHP principal
├── style.css              # Estilos CSS
├── script.js              # JavaScript frontend
├── .gitignore             # Arquivos ignorados pelo Git
├── README.md              # Este arquivo
├── logs/                  # Logs de debug
│   └── debug.log
└── resultados/            # CEPs extraídos organizados
    └── [estado]/
        └── [cidade]/
            └── [estado]-[cidade]-[bairro]-[data].txt
```

## 🎯 Exemplo de Uso

**Entrada:**
- Estado: SC (Santa Catarina)
- Cidade: Joinville
- Bairro: Costa e Silva

**Saída:**
- Arquivo: `sc-joinville-costa_e_silva-2025-10-06.txt`
- Localização: `resultados/sc/joinville/`
- Conteúdo: Lista de todos os CEPs do bairro

## ⚙️ Configurações

### Logs
Os logs são salvos em `logs/debug.log` e incluem:
- Requisições aos Correios
- Respostas AJAX
- Processo de paginação
- Erros e debug

### Estrutura de Pastas
Os resultados são organizados automaticamente:
```
resultados/
├── sc/                    # Estado (normalizado)
│   ├── joinville/         # Cidade (normalizada)
│   │   ├── sc-joinville-centro-2025-10-06.txt
│   │   ├── sc-joinville-costa_e_silva-2025-10-06.txt
│   │   └── ...
│   └── florianopolis/
└── sp/
    └── sao_paulo/
```

## 🔧 Funcionalidades Técnicas

### Paginação Automática
- Detecta automaticamente o total de CEPs
- Percorre todas as páginas (50 CEPs por página)
- Usa parâmetros `inicio`, `final` e `capt` conforme API dos Correios

### Normalização de Nomes
- Remove acentos (á → a, ç → c)
- Substitui espaços por underscore
- Remove caracteres especiais
- Exemplo: "São Paulo" → "sao_paulo"

### Sistema de Logs
- Logs centralizados em `logs/debug.log`
- Timestamp em todas as entradas
- Debug detalhado de requisições AJAX
- Monitoramento de paginação

## 🐛 Solução de Problemas

### CAPTCHA Inválido
- Verifique se digitou corretamente
- O CAPTCHA expira após alguns minutos
- Recarregue um novo CAPTCHA se necessário

### Nenhum Resultado
- Verifique se o bairro existe na base dos Correios
- Confirme a grafia exata do nome
- Alguns bairros podem não ter CEPs cadastrados

### Erro de Conexão
- Verifique sua conexão com a internet
- Os Correios podem estar temporariamente indisponíveis
- Consulte os logs em `logs/debug.log`

## 📝 Logs e Debug

Para debug, consulte o arquivo `logs/debug.log`:

```
[2025-10-06 18:49:02] Resposta AJAX recebida | Dados: {"erro":false,"total":263,"dados":[...]}
[2025-10-06 18:49:03] Página AJAX - Início: 51, Final: 100 | Dados: {...}
[2025-10-06 18:49:04] Arquivo salvo | Caminho: resultados/sc/joinville/sc-joinville-costa_e_silva-2025-10-06.txt | CEPs: 263
```

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está licenciado sob a Licença GPL-3.0 - veja o arquivo [LICENSE](LICENSE) para detalhes.

## ⚠️ Aviso Legal

Esta ferramenta é para fins educacionais e de pesquisa. Use com responsabilidade e respeite os termos de uso dos Correios. Não nos responsabilizamos pelo uso indevido desta ferramenta.

## 📞 Suporte

Se encontrar problemas ou tiver sugestões:

1. Abra uma [Issue](https://github.com/gabrielfilippi/extrair-ceps-correios/issues)
2. Consulte os logs em `logs/debug.log`
3. Verifique se está usando PHP 7.4+ com cURL habilitado

## 🎉 Agradecimentos

- [Correios](https://www.correios.com.br/) pela base de dados pública
- Comunidade PHP por recursos e documentação
- Contribuidores do projeto

---

**Desenvolvido com ❤️ para facilitar a extração de CEPs no Brasil**
