# streamlit_client.py
import os
import streamlit as st
import pandas as pd
import requests
import json
import time
from typing import List, Dict, Any
import base64
import streamlit.components.v1 as components

# API URL
API_BASE_URL = "http://localhost:8000"

# Initialize session state
if "chat_history" not in st.session_state:
    st.session_state.chat_history = []

if "documents_loaded" not in st.session_state:
    st.session_state.documents_loaded = False

def reset_chat():
    """
    Reset the chat history.
    """
    st.session_state.chat_history = []

def get_api_status():
    """
    Get the status of the API.
    """
    try:
        response = requests.get(f"{API_BASE_URL}/status")
        if response.status_code == 200:
            data = response.json()
            st.session_state.documents_loaded = data["documents_loaded"]
            return data
        else:
            st.error(f"Error checking API status: {response.status_code} - {response.text}")
            return None
    except Exception as e:
        st.error(f"Error connecting to API: {str(e)}")
        return None

def upload_and_process_pdf(uploaded_file):
    """
    Upload and process a PDF file via the API.
    """
    try:
        files = {"file": (uploaded_file.name, uploaded_file.getvalue(), "application/pdf")}
        with st.spinner("Processing PDF..."):
            response = requests.post(f"{API_BASE_URL}/upload", files=files)
            
        if response.status_code == 200:
            result = response.json()
            st.success(f"Successfully processed {uploaded_file.name} - {result.get('document_count', 0)} chunks created")
            st.session_state.documents_loaded = True
            return True
        else:
            st.error(f"Error uploading PDF: {response.status_code} - {response.text}")
            return False
    except Exception as e:
        st.error(f"Error uploading PDF: {str(e)}")
        return False

def chat_with_documents(query: str):
    """
    Send a query to the API for RAG processing.
    """
    # Format messages for API
    messages = []
    for msg in st.session_state.chat_history:
        messages.append({
            "role": msg["role"],
            "content": msg["content"]
        })
    
    # Add current query
    messages.append({
        "role": "user",
        "content": query
    })
    
    # Make API request
    try:
        payload = {"messages": messages}
        with st.spinner("Thinking..."):
            response = requests.post(
                f"{API_BASE_URL}/chat",
                json=payload
            )
        
        if response.status_code == 200:
            result = response.json()
            assistant_message = result["choices"][0]["message"]["content"]
            return assistant_message
        else:
            st.error(f"Error from API: {response.status_code} - {response.text}")
            error_detail = "Unknown error"
            try:
                error_data = response.json()
                error_detail = error_data.get("detail", "Unknown error")
            except:
                pass
            return f"Error: {error_detail}"
    except Exception as e:
        st.error(f"Error connecting to API: {str(e)}")
        return f"Error connecting to API: {str(e)}"

def clear_document_database():
    """
    Clear the document database via the API.
    """
    try:
        with st.spinner("Clearing document database..."):
            response = requests.post(f"{API_BASE_URL}/clear-database")
        
        if response.status_code == 200:
            st.session_state.documents_loaded = False
            return True
        else:
            st.error(f"Error clearing database: {response.status_code} - {response.text}")
            return False
    except Exception as e:
        st.error(f"Error connecting to API: {str(e)}")
        return False

def get_all_documents():
    """
    Get all documents from the API.
    """
    try:
        response = requests.get(f"{API_BASE_URL}/documents")
        if response.status_code == 200:
            data = response.json()
            df = pd.DataFrame(data["documents"])
            return df
        else:
            st.error(f"Error retrieving documents: {response.status_code} - {response.text}")
            return None
    except Exception as e:
        st.error(f"Error connecting to API: {str(e)}")
        return None

def main():
    # Page config – must be first Streamlit command!
    st.set_page_config(
        page_title="RAG ChatBot Client",
        page_icon="📚",
        layout="wide"
    )

    # 🔙 Go Back button
    st.markdown(
        """
        <style>
        .go-back-btn {
            display: inline-block;
            padding: 0.5em 1em;
            margin-bottom: 1em;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .go-back-btn:hover {
            background-color: #0056b3;
        }
        </style>
        <a href="http://127.0.0.1:8000/frontA" class="go-back-btn">🔙 Go Back</a>
        """,
        unsafe_allow_html=True
    )
    
    # Check API status
    api_status = get_api_status()
    if api_status is None:
        st.error("Unable to connect to the RAG API. Make sure it's running on localhost:8000")
        st.stop()
    
    # Sidebar
    with st.sidebar:
        st.title("📚 RAG Document Processor")
        
        # API Status
        st.subheader("API Status")
        if api_status:
            st.success(f"API Connected ✅")
            st.info(f"Documents in database: {api_status.get('document_count', 0)}")
        else:
            st.error("API Not Connected ❌")
        
        # PDF Upload
        st.subheader("Upload PDF Documents")
        uploaded_files = st.file_uploader(
            "Upload PDF files", 
            type="pdf", 
            accept_multiple_files=True
        )
        
        if uploaded_files:
            for uploaded_file in uploaded_files:
                if st.button(f"Process {uploaded_file.name}"):
                    upload_and_process_pdf(uploaded_file)
        
        # Reset Chat Button
        if st.button("Start New Chat", key="reset_chat"):
            reset_chat()
            st.success("Chat history cleared!")
        
        # Database Status
        st.subheader("Database Status")
        if st.session_state.documents_loaded:
            st.success("Documents loaded ✅")
            
            # Option to clear database
            if st.button("Clear Document Database"):
                if clear_document_database():
                    st.success("Document database has been cleared!")
                    st.experimental_rerun()
        else:
            st.warning("No documents loaded ❌")
    
    # Main content
    st.title("RAG ChatBot Client")
    
    # Display chat messages
    for message in st.session_state.chat_history:
        with st.chat_message(message["role"]):
            st.write(message["content"])
    
    # Chat input
    if query := st.chat_input("Ask a question about your documents"):
        # Add user message to chat history
        st.session_state.chat_history.append({"role": "user", "content": query})
        
        # Display user message
        with st.chat_message("user"):
            st.write(query)
        
        # Check if documents are loaded
        if not st.session_state.documents_loaded:
            with st.chat_message("assistant"):
                st.write("Please upload and process some PDF documents first.")
                st.session_state.chat_history.append({"role": "assistant", "content": "Please upload and process some PDF documents first."})
        else:
            # Generate response
            response = chat_with_documents(query)
            
            # Display assistant response
            with st.chat_message("assistant"):
                st.write(response)
                
            # Add assistant message to chat history
            st.session_state.chat_history.append({"role": "assistant", "content": response})
            
            # Display document visualization if available
            try:
                df = get_all_documents()
                if df is not None and not df.empty:
                    with st.expander("View Documents"):
                        st.dataframe(df[["source", "page", "content"]], use_container_width=True)
            except Exception as e:
                st.error(f"Error generating visualization: {str(e)}")

if __name__ == "__main__":
    main()