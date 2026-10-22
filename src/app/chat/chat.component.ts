import { HttpErrorResponse } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ChatMessage, ChatService } from '../core/chat.service';
import { apiErrorMessage } from '../core/api-error';

@Component({
  selector: 'app-chat',
  imports: [FormsModule],
  templateUrl: './chat.component.html',
  styleUrl: './chat.component.css'
})
export class ChatComponent implements OnInit {
  message = '';
  messages: ChatMessage[] = [];
  isLoading = true;
  isSending = false;
  errorMessage = '';

  constructor(private readonly chat: ChatService) {}

  ngOnInit(): void {
    this.loadMessages();
  }

  loadMessages(): void {
    this.isLoading = true;
    this.errorMessage = '';

    this.chat.getMessages().subscribe({
      next: ({ messages }) => {
        this.messages = messages;
        this.isLoading = false;
      },
      error: (error: HttpErrorResponse) => {
        this.errorMessage = apiErrorMessage(error, 'No se pudo cargar la conversación. Inténtalo de nuevo.');
        this.isLoading = false;
      },
    });
  }

  sendMessage(): void {
    const text = this.message.trim();
    if (!text || this.isSending) {
      return;
    }

    this.isSending = true;
    this.errorMessage = '';

    this.chat.sendMessage(text).subscribe({
      next: ({ messages }) => {
        this.messages.push(...messages);
        this.message = '';
        this.isSending = false;
      },
      error: (error: HttpErrorResponse) => {
        this.errorMessage = apiErrorMessage(error, 'No se pudo enviar el mensaje. Inténtalo de nuevo.');
        this.isSending = false;
      },
    });
  }
}
