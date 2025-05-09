# rag/retriever.py
from typing import List, Dict, Any
from langchain.vectorstores.chroma import Chroma
from langchain_core.documents import Document
import pandas as pd
import numpy as np

class DocumentRetriever:
    def __init__(self, vector_store: Chroma, k: int = 5):
        """
        Initialize the document retriever.
        
        Args:
            vector_store: ChromaDB vector store
            k: Number of documents to retrieve
        """
        self.vector_store = vector_store
        self.retriever = vector_store.as_retriever(search_kwargs={"k": k})
        self.k = k
        
    def retrieve(self, query: str) -> List[Document]:
        """
        Retrieve relevant documents for a query.
        """
        return self.retriever.invoke(query)
        
    def get_all_documents(self) -> pd.DataFrame:
        """
        Get all documents from the vector store with their embeddings.
        """
        response = self.vector_store.get(include=["metadatas", "documents", "embeddings"])
        
        df = pd.DataFrame({
            "id": response["ids"],
            "source": [metadata.get("source", "") for metadata in response["metadatas"]],
            "page": [metadata.get("page", -1) for metadata in response["metadatas"]],
            "document": response["documents"],
            "embedding": response["embeddings"],
        })
        
        return df
    
    def calculate_distances(self, df: pd.DataFrame, query_embedding: List[float]) -> pd.DataFrame:
        """
        Calculate distances between query embedding and document embeddings.
        """
        df["dist"] = df.apply(
            lambda row: np.linalg.norm(np.array(row["embedding"]) - query_embedding),
            axis=1,
        )
        return df