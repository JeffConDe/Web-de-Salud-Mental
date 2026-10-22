import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_URL } from './api-url';

export interface ChatMessage {
  id: number;
  sender: 'user' | 'assistant';
  content: string;
  created_at: string;
}

@Injectable({ providedIn: 'root' })
export class ChatService {
  constructor(private readonly http: HttpClient) {}

  getMessages(): Observable<{ messages: ChatMessage[] }> {
    return this.http.get<{ messages: ChatMessage[] }>(`${API_URL}/chat`);
  }

  sendMessage(content: string): Observable<{ messages: ChatMessage[] }> {
    return this.http.post<{ messages: ChatMessage[] }>(`${API_URL}/chat/messages`, { content });
  }
}
