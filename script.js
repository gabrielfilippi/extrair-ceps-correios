let cepsEncontrados = [];
let nomeArquivoAtual = '';

// Carregar CAPTCHA
document.getElementById('carregarCaptchaBtn').addEventListener('click', async () => {
    const uf = document.getElementById('uf').value;
    const localidade = document.getElementById('localidade').value;
    const bairro = document.getElementById('bairro').value;
    
    if (!uf || !localidade || !bairro) {
        mostrarAlerta('Preencha todos os campos primeiro!', 'error');
        return;
    }
    
    mostrarLoading(true);
    
    try {
        const response = await fetch('?action=carregar_captcha');
        const data = await response.json();
        
        if (data.sucesso) {
            document.getElementById('captchaImage').src = data.imagem;
            document.getElementById('captchaBox').classList.add('show');
            document.getElementById('captcha').value = '';
            document.getElementById('captcha').focus();
            mostrarAlerta('CAPTCHA carregado! Digite o texto acima.', 'info');
        } else {
            mostrarAlerta('Erro ao carregar CAPTCHA: ' + data.erro, 'error');
        }
    } catch (error) {
        mostrarAlerta('Erro de conexão: ' + error.message, 'error');
    }
    
    mostrarLoading(false);
});

// Limpar interface ao carregar CAPTCHA
document.getElementById('carregarCaptchaBtn').addEventListener('click', () => {
    // Esconder resultados e erros anteriores
    document.getElementById('resultado').classList.remove('show');
    document.getElementById('alertBox').classList.remove('show');
});

// Buscar CEPs
document.getElementById('buscarBtn').addEventListener('click', async () => {
    const formData = new FormData();
    formData.append('uf', document.getElementById('uf').value);
    formData.append('localidade', document.getElementById('localidade').value);
    formData.append('bairro', document.getElementById('bairro').value);
    formData.append('captcha', document.getElementById('captcha').value);
    
    if (!formData.get('captcha')) {
        mostrarAlerta('Digite o CAPTCHA!', 'error');
        return;
    }
    
    mostrarLoading(true);
    
    try {
        const response = await fetch('?action=buscar_ceps', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.sucesso) {
            cepsEncontrados = data.ceps;
            nomeArquivoAtual = data.arquivo;
            
            document.getElementById('totalCeps').textContent = data.total;
            document.getElementById('nomeArquivo').textContent = data.arquivo;
            document.getElementById('cepLista').textContent = data.ceps.join('\n');
            document.getElementById('resultado').classList.add('show');
            
            mostrarAlerta(`✅ ${data.total} CEPs encontrados e salvos!`, 'success');
            
            // Limpar CAPTCHA e esconder após sucesso
            document.getElementById('captcha').value = '';
            document.getElementById('captchaBox').classList.remove('show');
        } else {
            mostrarAlerta('❌ ' + data.erro, 'error');
        }
    } catch (error) {
        mostrarAlerta('Erro de conexão: ' + error.message, 'error');
    }
    
    mostrarLoading(false);
});

// Download
document.getElementById('downloadBtn').addEventListener('click', () => {
    const conteudo = cepsEncontrados.join('\n');
    const blob = new Blob([conteudo], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = nomeArquivoAtual;
    a.click();
    window.URL.revokeObjectURL(url);
});

// Enter no campo CAPTCHA
document.getElementById('captcha').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        document.getElementById('buscarBtn').click();
    }
});

// Funções auxiliares
function mostrarAlerta(mensagem, tipo) {
    const alertBox = document.getElementById('alertBox');
    alertBox.className = `alert alert-${tipo} show`;
    alertBox.textContent = mensagem;
    
    setTimeout(() => {
        alertBox.classList.remove('show');
    }, tipo === 'success' ? 5000 : 8000);
}

function mostrarLoading(show) {
    document.getElementById('loading').classList.toggle('show', show);
}
