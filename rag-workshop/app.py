from fastapi import FastAPI, UploadFile, Form
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
import shutil, os, tempfile
from dotenv import load_dotenv

# Import RAG logic from your existing app
from rag.document_loader import load_pdf, split_documents, get_document_ids
from rag.embeddings import GeminiEmbeddings
from rag.retriever import DocumentRetriever
from rag.chain import RagChain

load_dotenv()

# Constants
GOOGLE_API_KEY = os.getenv("GOOGLE_API_KEY")
PERSIST_DIR = "docs-db"
COLLECTION_NAME = "docs_store"

# Globals (instead of Streamlit session state)
chat_history = []
embedding_model = GeminiEmbeddings(api_key=GOOGLE_API_KEY)
vector_store = embedding_model.create_vector_store(
    collection_name=COLLECTION_NAME,
    persist_directory=PERSIST_DIR
)
retriever = DocumentRetriever(vector_store=vector_store, k=5)
rag_chain = RagChain(retriever=retriever, api_key=GOOGLE_API_KEY)

# Create app
app = FastAPI()

# Allow frontend from Symfony
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:8000", "http://127.0.0.1:8000"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

def format_chat_history():
    formatted = ""
    for msg in chat_history:
        formatted += f"{msg['role'].upper()}: {msg['content']}\n"
    return formatted

@app.post("/chat")
async def chat(message: str = Form(...)):
    try:
        chat_history.append({"role": "user", "content": message})
        chat_log = format_chat_history()
        response = rag_chain.invoke(message, chat_log)
        answer = response["answer"]
        chat_history.append({"role": "assistant", "content": answer})
        return {"success": True, "message": {"content": answer, "role": "assistant"}}
    except Exception as e:
        return {"success": False, "error": str(e)}

@app.post("/upload")
async def upload(pdf_file: UploadFile):
    try:
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as temp_file:
            shutil.copyfileobj(pdf_file.file, temp_file)
            temp_path = temp_file.name

        documents = load_pdf(temp_path)
        splits = split_documents(documents)
        document_ids = get_document_ids(splits)

        embedding_model.add_documents_to_store(vector_store, splits, document_ids)
        os.unlink(temp_path)

        return {"success": True, "message": "PDF uploaded and processed."}
    except Exception as e:
        return {"success": False, "error": str(e)}

@app.post("/reset")
def reset():
    global chat_history
    chat_history = []
    return {"success": True}

@app.post("/clear")
def clear():
    global embedding_model, vector_store, retriever, rag_chain
    try:
        shutil.rmtree(PERSIST_DIR, ignore_errors=True)
        os.makedirs(PERSIST_DIR, exist_ok=True)

        embedding_model = GeminiEmbeddings(api_key=GOOGLE_API_KEY)
        vector_store = embedding_model.create_vector_store(
            collection_name=COLLECTION_NAME,
            persist_directory=PERSIST_DIR
        )
        retriever = DocumentRetriever(vector_store=vector_store, k=5)
        rag_chain = RagChain(retriever=retriever, api_key=GOOGLE_API_KEY)

        return {"success": True, "message": "Document database cleared."}
    except Exception as e:
        return {"success": False, "error": str(e)}