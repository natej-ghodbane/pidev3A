# rag/embeddings.py
import os
from typing import List, Dict, Any, Union
import google.generativeai as genai
from langchain_google_genai import GoogleGenerativeAIEmbeddings
from langchain.vectorstores.chroma import Chroma
from langchain_core.documents import Document

class GeminiEmbeddings:
    def __init__(self, api_key: str, model_name: str = "models/embedding-001"):
        """
        Initialize Gemini embeddings.
        
        Args:
            api_key: Google API key
            model_name: Name of the embedding model
        """
        genai.configure(api_key=api_key)
        self.embedding_model = GoogleGenerativeAIEmbeddings(model=model_name)
        
    def get_embedding(self, text: str) -> List[float]:
        """
        Get embedding for a single text.
        """
        return self.embedding_model.embed_query(text)
        
    def create_vector_store(self, 
                           collection_name: str = "docs_store", 
                           persist_directory: str = "docs-db") -> Chroma:
        """
        Create a vector store for document storage, or load an existing one.
        
        This will either create a new vector store or load an existing one
        from the persist_directory if it exists.
        """
        # Check if the persistence directory exists and has content
        if os.path.exists(persist_directory) and os.path.isdir(persist_directory):
            dir_contents = os.listdir(persist_directory)
            if dir_contents:  # Directory is not empty
                try:
                    # Try to load existing vector store
                    return Chroma(
                        collection_name=collection_name,
                        embedding_function=self.embedding_model,
                        persist_directory=persist_directory
                    )
                except Exception as e:
                    print(f"Error loading existing vector store: {e}")
                    # If loading fails, create a new one
        
        # Create a new vector store
        return Chroma(
            collection_name=collection_name,
            embedding_function=self.embedding_model,
            persist_directory=persist_directory
        )
        
    def add_documents_to_store(self, 
                              vector_store: Chroma, 
                              documents: List[Document], 
                              document_ids: List[str]) -> None:
        """
        Add documents to the vector store.
        """
        vector_store.add_documents(documents, ids=document_ids)
        vector_store.persist()