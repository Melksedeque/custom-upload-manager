# Custom Upload Manager - Guia do Administrador WordPress

![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-green.svg)
![Tested](https://img.shields.io/badge/Tested%20up%20to-6.4-green.svg)

## 📋 Visão Geral

O **Custom Upload Manager** é um plugin WordPress que permite aos usuários logados fazer upload e organizar arquivos em pastas personalizadas. Este guia é destinado a administradores de sites WordPress.

## ⚡ Instalação Rápida

### Método 1: Upload via Admin WordPress

1. **Baixe o plugin** (arquivo .zip)
2. **Acesse o Admin WordPress** → Plugins → Adicionar Novo
3. **Clique em "Enviar Plugin"** → Escolher arquivo
4. **Selecione o arquivo** `custom-upload-manager.zip`
5. **Clique em "Instalar Agora"**
6. **Ative o plugin** após a instalação

### Método 2: Upload via FTP

```bash
# Extraia o arquivo ZIP
unzip custom-upload-manager.zip

# Envie via FTP para:
/wp-content/plugins/custom-upload-manager/

# Ative no painel WordPress
Plugins → Custom Upload Manager → Ativar
```

### Método 3: WP-CLI (Avançado)

```bash
# Instale via WP-CLI
wp plugin install custom-upload-manager.zip
wp plugin activate custom-upload-manager
```

## 🔧 Requisitos do Sistema

### Requisitos Mínimos
- **WordPress:** 5.0 ou superior
- **PHP:** 7.4 ou superior
- **MySQL:** 5.7 ou superior
- **Memória PHP:** 128MB (recomendado: 256MB)
- **Espaço em disco:** 50MB livres

### Requisitos Recomendados
- **WordPress:** 6.0 ou superior
- **PHP:** 8.0 ou superior
- **MySQL:** 8.0 ou superior
- **Memória PHP:** 512MB
- **SSL:** Certificado válido (HTTPS)

### Verificação de Compatibilidade

```php
// Adicione ao functions.php para verificar compatibilidade
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo 'Custom Upload Manager requer PHP 7.4 ou superior.';
        echo '</p></div>';
    });
}
```

## ⚙️ Configuração Inicial

### 1. Verificação Pós-Instalação

Após ativar o plugin, verifique:

- ✅ **Pasta de uploads criada:** `/wp-content/uploads/custom-uploads/`
- ✅ **Permissões corretas:** 755 para pastas, 644 para arquivos
- ✅ **Shortcode disponível:** `[custom_upload_manager]`
- ✅ **Scripts carregados:** CSS e JavaScript no frontend

### 2. Configuração de Permissões

```bash
# Defina permissões corretas via SSH
chmod 755 /wp-content/uploads/custom-uploads/
chown www-data:www-data /wp-content/uploads/custom-uploads/
```

### 3. Configuração do Servidor Web

#### Apache (.htaccess)
```apache
# Adicione ao .htaccess da pasta uploads
<Files "*">
    Order Deny,Allow
    Deny from all
</Files>

<FilesMatch "\.(jpg|jpeg|png|gif|pdf|doc|docx|txt)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>
```

#### Nginx
```nginx
# Adicione ao nginx.conf
location ~* /wp-content/uploads/custom-uploads/.*\.(php|php5|phtml|pl|py|jsp|asp|sh|cgi)$ {
    deny all;
}
```

## 📄 Implementação em Páginas/Posts

### Shortcode Básico

```php
// Adicione em qualquer página ou post
[custom_upload_manager]
```

### Shortcode com Parâmetros (Futuro)

```php
// Planejado para versões futuras
[custom_upload_manager max_files="10" allowed_types="pdf,doc,jpg"]
```

### Implementação via PHP

```php
// Adicione ao template do tema
<?php
if (function_exists('cum_display_upload_manager')) {
    cum_display_upload_manager();
}
?>
```

### Implementação via Widget (Futuro)

```php
// Planejado: Widget para sidebar
Aparência → Widgets → Custom Upload Manager
```

## 🛡️ Configurações de Segurança

### 1. Tipos de Arquivo Permitidos

Por padrão, o plugin permite:
- **Documentos:** PDF, DOC, DOCX, TXT
- **Imagens:** JPG, JPEG, PNG, GIF
- **Planilhas:** XLS, XLSX, CSV

### 2. Limites de Upload

```php
// Configurações recomendadas no wp-config.php
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '50M');
ini_set('max_execution_time', 300);
ini_set('max_input_vars', 3000);
```

### 3. Proteção de Diretório

```php
// O plugin cria automaticamente:
/wp-content/uploads/custom-uploads/.htaccess
/wp-content/uploads/custom-uploads/index.php
```

## 👥 Gerenciamento de Usuários

### Permissões Necessárias

- **Usuários logados:** Podem fazer upload e gerenciar seus arquivos
- **Visitantes:** Não têm acesso ao sistema
- **Administradores:** Acesso total (futuro: painel admin)

### Isolamento de Dados

- Cada usuário vê apenas seus próprios arquivos
- Pastas são isoladas por ID do usuário
- Não há compartilhamento entre usuários

## 📊 Monitoramento e Manutenção

### 1. Logs de Atividade

```php
// Ative logs no wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Logs ficam em:
/wp-content/debug.log
```

### 2. Limpeza Automática (Futuro)

```php
// Planejado: Limpeza de arquivos órfãos
wp cron event run cum_cleanup_orphaned_files
```

### 3. Backup de Arquivos

```bash
# Inclua no backup do site
tar -czf backup-uploads.tar.gz /wp-content/uploads/custom-uploads/
```

## 🚨 Solução de Problemas

### Problemas Comuns

#### 1. "Erro ao criar pasta de upload"
```bash
# Solução: Verificar permissões
chmod 755 /wp-content/uploads/
chown www-data:www-data /wp-content/uploads/
```

#### 2. "Arquivo muito grande"
```php
// Solução: Aumentar limites no .htaccess
php_value upload_max_filesize 20M
php_value post_max_size 50M
php_value max_execution_time 300
```

#### 3. "Shortcode não funciona"
```php
// Verificar se o plugin está ativo
if (!is_plugin_active('custom-upload-manager/custom-upload-manager.php')) {
    // Plugin não está ativo
}
```

#### 4. "CSS/JS não carrega"
```php
// Limpar cache e verificar
wp cache flush

// Verificar se os arquivos existem
/wp-content/plugins/custom-upload-manager/css/style.css
/wp-content/plugins/custom-upload-manager/js/scripts.js
```

### Debug Mode

```php
// Ative debug no wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## 🔄 Atualizações

### Processo de Atualização

1. **Faça backup** dos arquivos e banco de dados
2. **Desative o plugin** temporariamente
3. **Substitua os arquivos** pela nova versão
4. **Reative o plugin**
5. **Teste as funcionalidades**

### Compatibilidade com Outros Plugins

#### Plugins Testados ✅
- **Yoast SEO:** Compatível
- **Contact Form 7:** Compatível
- **WooCommerce:** Compatível
- **Elementor:** Compatível

#### Possíveis Conflitos ⚠️
- **Plugins de cache:** Podem precisar de exclusão de cache
- **Plugins de segurança:** Podem bloquear uploads
- **Plugins de otimização:** Podem minificar CSS/JS

## 📈 Performance

### Otimizações Recomendadas

```php
// wp-config.php - Otimizações
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

### Cache

```php
// Exclua do cache (se necessário)
/wp-content/uploads/custom-uploads/*
```

## 📞 Suporte

### Informações para Suporte

Ao solicitar suporte, inclua:

- **Versão do WordPress:** `wp --version`
- **Versão do PHP:** `php --version`
- **Versão do plugin:** Veja em Plugins → Instalados
- **Logs de erro:** `/wp-content/debug.log`
- **Configuração do servidor:** Apache/Nginx

### Recursos Úteis

- **Documentação técnica:** [README.md](README.md)
- **Guia do usuário:** [README-USER.md](README-USER.md)
- **WordPress Codex:** https://codex.wordpress.org/
- **PHP Manual:** https://www.php.net/manual/

## 🔮 Próximas Versões

### Funcionalidades Planejadas

- **Painel Admin:** Configurações centralizadas
- **Relatórios:** Dashboard de uso e estatísticas
- **Bulk Operations:** Operações em lote
- **API REST:** Integração com aplicações externas
- **Multi-site:** Suporte para redes WordPress

---

**Desenvolvido com ❤️ para administradores WordPress**

*"Que a Força esteja com sua administração!"* ⚡