# Sicoob Production Setup Walkthrough

Este guia fornece os passos necessários para extrair sua chave privada e configurar o deploy para produção agora que o suporte a Base64 foi implementado.

## 1. Como extrair a Chave Privada

Como você tem o arquivo `.pfx`, o OpenSSL é a melhor ferramenta para isso.

### Opção A: A partir do arquivo .pfx (Recomendado)
Execute no terminal:
```bash
# Extrair o certificado (pode pedir a senha do PFX)
openssl pkcs12 -in seu_arquivo.pfx -clcerts -nokeys -out certificate.pem

# Extrair a chave privada protegida
openssl pkcs12 -in seu_arquivo.pfx -nocerts -out private_encrypted.key

# Remover a senha da chave privada (para o SicoobService ler sem interrupção)
openssl rsa -in private_encrypted.key -out private.key
```

### Opção B: A partir do .pem (se ele já contiver a chave)
Se o seu `.pem` atual já tiver os blocos `-----BEGIN PRIVATE KEY-----`, você pode apenas recortar essa parte para um arquivo `private.key`.

---

## 2. Gerando as strings Base64 para o Kamal

Agora que você tem os arquivos `certificate.pem` e `private.key` (sem senha), gere o Base64:

```bash
# Gerar base64 do certificado (copie a saída)
base64 -i certificate.pem

# Gerar base64 da chave privada (copie a saída)
base64 -i private.key
```

---

## 3. Configurando o [.kamal/secrets](file:///Users/vicentebrida/dev/sul-previdencia-adesao/.kamal/secrets)

No seu arquivo [.kamal/secrets](file:///Users/vicentebrida/dev/sul-previdencia-adesao/.kamal/secrets), atualize com os novos valores:

```env
SICOOB_BASE_URL="https://api.sicoob.com.br/pix/api/v2"
SICOOB_AUTH_URL="https://auth.sicoob.com.br/oauth2/token"
SICOOB_CLIENT_ID="[CLIENT_ID_DE_PRODUCAO]"
SICOOB_CERTIFICATE_BASE64="[CONTEUDO_BASE64_DO_CERTIFICADO]"
SICOOB_PRIVATE_KEY_BASE64="[CONTEUDO_BASE64_DA_CHAVE_PRIVADA]"
```

---

## O que mudou no código?

1. **[SicoobService.php](file:///Users/vicentebrida/dev/sul-previdencia-adesao/src/Services/SicoobService.php)**: Agora ele verifica se `certificateBase64` e `privateKeyBase64` estão preenchidos. Se sim, ele cria automaticamente arquivos temporários em `tmp/` para que o cliente HTTP possa usá-los com segurança no container.
2. **[deploy.yml](file:///Users/vicentebrida/dev/sul-previdencia-adesao/config/deploy.yml)**: Adicionamos as novas variáveis para o Kamal injetar no container.
3. **[app.php](file:///Users/vicentebrida/dev/sul-previdencia-adesao/config/app.php)**: Adicionamos o mapeamento das variáveis de ambiente.

> [!TIP]
> Após configurar os secrets, basta rodar `kamal deploy` para subir com as credenciais de produção.
