# api.py
import os
import tempfile
import shutil
from typing import List, Dict, Any, Optional
from pydantic import BaseModel
from fastapi import FastAPI, File, UploadFile, HTTPException, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
import uvicorn
from dotenv import load_dotenv

from rag.document_loader import load_pdf, split_documents, get_document_ids
from rag.embeddings import GeminiEmbeddings
from rag.retriever import DocumentRetriever
from rag.chain import RagChain

# Load environment variables
load_dotenv()

# Constants
GOOGLE_API_KEY = os.getenv("GOOGLE_API_KEY")
UPLOAD_DIR = "uploads"
PERSIST_DIR = "docs-db"
COLLECTION_NAME = "docs_store"

# Ensure directories exist
os.makedirs(UPLOAD_DIR, exist_ok=True)
os.makedirs(PERSIST_DIR, exist_ok=True)

# Initialize FastAPI app
app = FastAPI(
    title="RAG Chatbot API",
    description="API for RAG Chatbot with document processing and query capabilities",
    version="1.0.0",
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Pydantic models for request/response
class Message(BaseModel):
    role: str
    content: str

class ChatRequest(BaseModel):
    messages: List[Message]

class ChatResponse(BaseModel):
    id: str
    object: str = "chat.completion"
    created: int
    model: str = "rag-chatbot"
    choices: List[Dict[str, Any]]
    usage: Dict[str, int]

class DocumentResponse(BaseModel):
    success: bool
    message: str
    document_count: Optional[int] = None

class StatusResponse(BaseModel):
    documents_loaded: bool
    document_count: Optional[int] = None
    message: str

# Global variables to store components
embedding_model = None
vector_store = None
retriever = None
rag_chain = None
documents_loaded = False

def initialize_components():
    """
    Initialize RAG components.
    """
    global embedding_model, vector_store, retriever, rag_chain, documents_loaded
    
    # Check if API key is available
    if not GOOGLE_API_KEY:
        raise ValueError("Please set your GOOGLE_API_KEY in .env file or environment variables")
    
    # Initialize embeddings model
    embedding_model = GeminiEmbeddings(api_key=GOOGLE_API_KEY)
    
    # Initialize vector store
    vector_store = embedding_model.create_vector_store(
        collection_name=COLLECTION_NAME,
        persist_directory=PERSIST_DIR
    )
    
    # Check if there are documents in the vector store
    documents_loaded = check_vector_store_has_data(vector_store)
    
    # Initialize retriever
    retriever = DocumentRetriever(vector_store=vector_store, k=5)
    
    # Initialize RAG chain
    rag_chain = RagChain(retriever=retriever, api_key=GOOGLE_API_KEY)

def check_vector_store_has_data(vector_store):
    """
    Check if the vector store has data.
    """
    try:
        response = vector_store.get()
        return len(response.get("ids", [])) > 0
    except Exception:
        return False

def format_chat_history(messages: List[Message]) -> str:
    """
    Format chat history for the RAG chain.
    """
    formatted_history = ""
    for message in messages:
        role = message.role
        content = message.content
        formatted_history += f"{role.upper()}: {content}\n"
    return formatted_history

async def process_pdf_file(file_path: str):
    """
    Process a PDF file and add it to the vector store.
    """
    global documents_loaded
    
    try:
        # Load and process the PDF
        documents = load_pdf(file_path)
        splits = split_documents(documents)
        document_ids = get_document_ids(splits)
        
        # Add documents to vector store
        embedding_model.add_documents_to_store(
            vector_store,
            splits,
            document_ids
        )
        
        documents_loaded = True
        return len(splits)
    except Exception as e:
        raise ValueError(f"Error processing PDF: {str(e)}")

@app.on_event("startup")
async def startup_event():
    """
    Initialize components on startup.
    """
    try:
        initialize_components()
    except Exception as e:
        print(f"Error initializing components: {str(e)}")

@app.get("/", response_model=Dict[str, str])
async def root():
    """
    Root endpoint.
    """
    return {"message": "RAG Chatbot API is running"}

@app.get("/status", response_model=StatusResponse)
async def get_status():
    """
    Get the status of the document database.
    """
    global documents_loaded, vector_store
    
    doc_count = None
    if documents_loaded and vector_store:
        try:
            response = vector_store.get()
            doc_count = len(response.get("ids", []))
        except Exception:
            pass
    
    return StatusResponse(
        documents_loaded=documents_loaded,
        document_count=doc_count,
        message="Database status retrieved successfully"
    )

@app.post("/upload", response_model=DocumentResponse)
async def upload_document(
    background_tasks: BackgroundTasks,
    file: UploadFile = File(...)
):
    """
    Upload and process a PDF document.
    """
    if not file.filename.endswith('.pdf'):
        raise HTTPException(status_code=400, detail="Only PDF files are allowed")
    
    with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as temp_file:
        try:
            # Write uploaded file to temp file
            content = await file.read()
            temp_file.write(content)
            temp_path = temp_file.name
            
            # Process the PDF file
            doc_count = await process_pdf_file(temp_path)
            
            return DocumentResponse(
                success=True,
                message=f"Successfully processed {file.filename}",
                document_count=doc_count
            )
        except Exception as e:
            raise HTTPException(status_code=500, detail=str(e))
        finally:
            # Clean up temp file
            if os.path.exists(temp_path):
                os.unlink(temp_path)

@app.post("/chat", response_model=ChatResponse)
async def chat(request: ChatRequest):
    """
    Chat with the RAG model.
    """
    global documents_loaded, rag_chain
    
    if not documents_loaded:
        raise HTTPException(
            status_code=400, 
            detail="No documents loaded. Please upload documents first."
        )
    
    if not request.messages or len(request.messages) == 0:
        raise HTTPException(status_code=400, detail="No messages provided")
    
    # Get the last message as the query
    user_messages = [msg for msg in request.messages if msg.role.lower() == "user"]
    if not user_messages:
        raise HTTPException(status_code=400, detail="No user messages found")
    
    query = user_messages[-1].content
    chat_history = format_chat_history(request.messages[:-1])
    
    try:
        # Generate response using RAG chain
        response = rag_chain.invoke(query, chat_history)
        answer = response["answer"]
        
        # Format response like OpenAI's API
        import time
        import uuid
        
        return ChatResponse(
            id=f"chatcmpl-{str(uuid.uuid4())}",
            created=int(time.time()),
            choices=[{
                "index": 0,
                "message": {
                    "role": "assistant",
                    "content": answer
                },
                "finish_reason": "stop"
            }],
            usage={
                "prompt_tokens": len(" ".join([m.content for m in request.messages]).split()),
                "completion_tokens": len(answer.split()),
                "total_tokens": len(" ".join([m.content for m in request.messages]).split()) + len(answer.split())
            }
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error generating response: {str(e)}")

@app.post("/clear-database", response_model=StatusResponse)
async def clear_database():
    """
    Clear the document database.
    """
    global documents_loaded, vector_store, retriever, rag_chain
    
    try:
        # Delete vector store persistence directory and recreate it
        shutil.rmtree(PERSIST_DIR, ignore_errors=True)
        os.makedirs(PERSIST_DIR, exist_ok=True)
        
        # Reinitialize components
        initialize_components()
        
        return StatusResponse(
            documents_loaded=documents_loaded,
            document_count=0,
            message="Document database has been cleared successfully"
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error clearing database: {str(e)}")

@app.get("/documents", response_model=Dict[str, Any])
async def get_documents():
    """
    Get all documents in the database.
    """
    global documents_loaded, retriever
    
    if not documents_loaded:
        raise HTTPException(
            status_code=400, 
            detail="No documents loaded. Please upload documents first."
        )
    
    try:
        # Get all documents
        df = retriever.get_all_documents()
        
        # Convert DataFrame to JSON-friendly format
        documents = []
        for _, row in df.iterrows():
            documents.append({
                "id": row.get("id", ""),
                "source": row.get("source", ""),
                "page": row.get("page", -1),
                "content": row.get("document", ""),
            })
        
        return {
            "count": len(documents),
            "documents": documents
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error retrieving documents: {str(e)}")

if __name__ == "__main__":
    uvicorn.run("api:app", host="0.0.0.0", port=8000, reload=True)