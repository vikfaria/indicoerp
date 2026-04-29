export interface Address {
  name: string;
  address_line_1: string;
  address_line_2?: string;
  city: string;
  state: string;
  country: string;
  zip_code: string;
}

export interface Customer {
  id: number;
  user_id?: number;
  customer_code: string;
  company_name: string;
  contact_person_name: string;
  contact_person_email: string;
  contact_person_mobile?: string;
  tax_number?: string;
  payment_terms?: string;
  billing_address: Address;
  shipping_address: Address;
  same_as_billing: boolean;
  notes?: string;
  creator_id: number;
  created_by: number;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    name: string;
    avatar?: string;
  };
  creator?: {
    id: number;
    name: string;
  };
  created_by_user?: {
    id: number;
    name: string;
  };
}

export interface CustomerFormData {
  user_id?: number;
  company_name: string;
  contact_person_name: string;
  contact_person_email: string;
  contact_person_mobile?: string;
  tax_number?: string;
  payment_terms?: string;
  billing_address: Address;
  shipping_address: Address;
  same_as_billing: boolean;
  notes?: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  mobile_no?: string;
}