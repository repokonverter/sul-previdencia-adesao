import re
import pandas as pd
from pypdf import PdfReader

# Abre o arquivo PDF
pdf_path = "cbo2002_lista.pdf"
reader = PdfReader(pdf_path)

data = []
# Expressão regular para capturar Tipo (Sinônimo/Ocupação/Família), Código CBO e Descrição/Termo
pattern = re.compile(r'^(Sinônimo|Ocupação|Família|Ocupacao|Familia)\s*(\d{4}(?:-\d{2})?)\s+(.*)$', re.IGNORECASE)

print("Processando páginas do PDF...")
for page in reader.pages:
    text = page.extract_text()
    if not text:
        continue
    for line in text.split('\n'):
        line = line.strip()
        # Ignora cabeçalhos e rodapés do relatório
        if not line or "Relatório de Titulo" in line or "Classificação Brasileira de Ocupações" in line or "Data:" in line or "Hora:" in line or "CBO 2002 Títulos Tipo" in line:
            continue

        line = re.sub(r'^\d*CBO2002.*', '', line).strip()
        if not line:
            continue

        m = pattern.match(line)
        if m:
            tipo, codigo, termo = m.groups()
            data.append({
                "codigo": codigo.strip(),
                "termo": termo.strip(),
                "tipo": tipo.strip().capitalize()
            })

# Cria a tabela e exporta para CSV
df = pd.DataFrame(data)
df.to_csv("cbo2002_convertido.csv", index=False, encoding="utf-8-sig")
print(f"Sucesso! Foram convertidas {len(df)} linhas em 'cbo2002_convertido.csv'.")
