# rag/chain.py
from typing import List, Dict, Any
from langchain_google_genai import ChatGoogleGenerativeAI
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.runnables import RunnableParallel, RunnablePassthrough
from langchain_core.output_parsers import StrOutputParser
from langchain_core.documents import Document

class RagChain:
    def __init__(self, retriever, api_key: str, model_name: str = "gemini-1.5-pro"):
        """
        Initialize the RAG chain.
        
        Args:
            retriever: Document retriever
            api_key: Google API key
            model_name: Name of the LLM model
        """
        self.retriever = retriever
        self.llm = ChatGoogleGenerativeAI(
            model=model_name,
            google_api_key=api_key,
            temperature=0.0
        )
        
        self.prompt = ChatPromptTemplate.from_template("""
        You are an assistant for question-answering tasks.
        Given the following extracted parts of a long document and a question, create a final answer with references ("SOURCES").
        If you don't know the answer, just say that you don't know. Don't try to make up an answer.
        ALWAYS return a "SOURCES" part in your answer.
        
        CHAT HISTORY:
        {chat_history}
        
        QUESTION: {question}
        =========
        {source_documents}
        =========
        FINAL ANSWER: """)
        
        self.setup_chain()
        
    def setup_chain(self):
        """
        Set up the RAG chain.
        """
        def format_docs(docs: List[Document]) -> str:
            return "\n\n".join(
                f"Content: {doc.page_content}\nSource: {doc.metadata.get('source', 'Unknown')}, Page: {doc.metadata.get('page', 'Unknown')}" 
                for doc in docs
            )
            
        # Chain that processes documents and question to generate an answer
        self.rag_chain_from_docs = (
            RunnablePassthrough.assign(
                source_documents=lambda x: format_docs(x["source_documents"])
            )
            | self.prompt
            | self.llm
            | StrOutputParser()
        )
        
        # Complete RAG chain that retrieves documents and generates an answer
        self.rag_chain = RunnableParallel(
            {
                "source_documents": lambda x: self.retriever.retrieve(x["question"]),
                "question": lambda x: x["question"],
                "chat_history": lambda x: x["chat_history"]
            }
        ).assign(answer=self.rag_chain_from_docs)
    
    def invoke(self, question: str, chat_history: str = "") -> Dict[str, Any]:
        """
        Invoke the RAG chain.
        
        Args:
            question: User question
            chat_history: Chat history as a formatted string
            
        Returns:
            Dictionary with answer and source documents
        """
        return self.rag_chain.invoke({"question": question, "chat_history": chat_history})