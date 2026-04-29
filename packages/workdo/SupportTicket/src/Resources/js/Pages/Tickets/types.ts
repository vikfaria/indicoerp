export interface Ticket {
  id: number;
  ticket_id: string;
  name: string;
  email: string;
  user_id?: string;
  account_type: 'staff' | 'client' | 'vendor' | 'custom';
  category: number;
  subject: string;
  status: 'open' | 'in_progress' | 'closed' | 'on_hold';
  description?: string;
  attachments?: any[];
  note?: string;
  creator_id?: number;
  created_by?: number;
  created_at: string;
  updated_at: string;
}

export interface TicketCategory {
  id: number;
  name: string;
  color: string;
  creator_id?: number;
  created_by?: number;
  created_at: string;
  updated_at: string;
}

export interface TicketConversation {
  id: number;
  ticket_id: number;
  message: string;
  sender_type: string;
  sender_name: string;
  sender_email: string;
  attachments?: any[];
  creator_id?: number;
  created_by?: number;
  created_at: string;
  updated_at: string;
}