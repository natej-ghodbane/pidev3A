# rag/visualization.py
import pandas as pd
import numpy as np
import plotly.express as px
from umap import UMAP
from typing import List, Dict, Any

class Visualizer:
    def __init__(self, embedding_model):
        """
        Initialize the visualizer.
        """
        self.embedding_model = embedding_model
        self.umap_reducer = UMAP(n_components=2, random_state=42)
        
    def prepare_visualization_data(self, 
                                  df: pd.DataFrame, 
                                  question: str, 
                                  answer: str) -> pd.DataFrame:
        """
        Prepare data for visualization.
        
        Args:
            df: DataFrame with document embeddings
            question: User question
            answer: Generated answer
            
        Returns:
            DataFrame with question, answer, and document embeddings
        """
        # Check if DataFrame is empty
        if df.empty:
            raise ValueError("Document DataFrame is empty. Please upload documents first.")
            
        # Add question and answer embeddings
        question_embedding = self.embedding_model.get_embedding(question)
        answer_embedding = self.embedding_model.get_embedding(answer)
        
        question_row = pd.DataFrame({
            "id": ["question"],
            "document": [question],
            "type": ["question"],
            "embedding": [question_embedding],
        })
        
        answer_row = pd.DataFrame({
            "id": ["answer"],
            "document": [answer],
            "type": ["answer"],
            "embedding": [answer_embedding],
        })
        
        # Add document type
        df["type"] = "document"
        
        # Make sure all DataFrames have the same columns before concatenation
        required_cols = ["id", "document", "type", "embedding"]
        for col in required_cols:
            if col not in df.columns:
                df[col] = None if col != "embedding" else [[0.0] * len(question_embedding)]
        
        df = df[required_cols]  # Only keep required columns
        
        # Combine dataframes
        combined_df = pd.concat([question_row, answer_row, df], ignore_index=True)
        
        # Calculate distances from question
        combined_df["dist"] = combined_df.apply(
            lambda row: np.linalg.norm(np.array(row["embedding"]) - question_embedding) if isinstance(row["embedding"], list) else float('inf'),
            axis=1,
        )
        
        return combined_df
        
    def generate_umap_visualization(self, df: pd.DataFrame) -> Dict[str, Any]:
        """
        Generate UMAP visualization.
        
        Args:
            df: DataFrame with embeddings
            
        Returns:
            Plotly figure
        """
        # Make sure all embeddings are valid lists
        valid_rows = df[df["embedding"].apply(lambda x: isinstance(x, list) and len(x) > 0)].copy()
        
        if len(valid_rows) < 2:
            # Not enough data for UMAP, create a simple scatter plot instead
            valid_rows["umap_x"] = [0, 1][:len(valid_rows)]
            valid_rows["umap_y"] = [0, 0][:len(valid_rows)]
        else:
            # Extract embeddings
            embeddings = np.array(valid_rows["embedding"].tolist())
            
            # Reduce dimensions with UMAP
            umap_embeddings = self.umap_reducer.fit_transform(embeddings)
            
            # Add UMAP coordinates to dataframe
            valid_rows["umap_x"] = umap_embeddings[:, 0]
            valid_rows["umap_y"] = umap_embeddings[:, 1]
        
        # Create plot
        fig = px.scatter(
            valid_rows, 
            x="umap_x", 
            y="umap_y", 
            color="type",
            hover_data=["document", "dist"],
            color_discrete_map={
                "question": "#FF0000",  # Red
                "answer": "#00FF00",    # Green
                "document": "#0000FF"   # Blue
            },
            title="UMAP Visualization of Document Embeddings",
            labels={"umap_x": "UMAP Dimension 1", "umap_y": "UMAP Dimension 2"}
        )
        
        return fig