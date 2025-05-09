# rag/document_loader.py
import os
from typing import List
import hashlib
import json

from langchain_community.document_loaders import PyPDFLoader
from langchain.text_splitter import RecursiveCharacterTextSplitter
from langchain_core.documents import Document


def load_pdf(file_path: str) -> List[Document]:
    """
    Load a PDF file and return a list of documents.
    """
    loader = PyPDFLoader(file_path)
    documents = loader.load()
    return documents


def split_documents(documents: List[Document], chunk_size=1000, chunk_overlap=200) -> List[Document]:
    """
    Split documents into chunks.
    """
    text_splitter = RecursiveCharacterTextSplitter(
        chunk_size=chunk_size, 
        chunk_overlap=chunk_overlap, 
        add_start_index=True
    )
    splits = text_splitter.split_documents(documents)
    return splits


def stable_hash(doc: Document) -> str:
    """
    Create a stable hash for a document based on its metadata.
    """
    return hashlib.sha1(json.dumps(doc.metadata, sort_keys=True).encode()).hexdigest()


def get_document_ids(documents: List[Document]) -> List[str]:
    """
    Get a list of document IDs.
    """
    return list(map(stable_hash, documents))