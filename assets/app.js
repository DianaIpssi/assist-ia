import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';

function initAddressAutocomplete() {
  const cityInputs = document.querySelectorAll('[data-address-autocomplete="city"]');

  cityInputs.forEach((input) => {
    if (input.dataset.addressAutocompleteBound === '1') {
      return;
    }
    input.dataset.addressAutocompleteBound = '1';

    const suggestionsSelector = input.getAttribute('data-suggestions-target');
    const postalSelector = input.getAttribute('data-postal-target');
    const suggestionsContainer = suggestionsSelector ? document.querySelector(suggestionsSelector) : null;

    if (!suggestionsContainer) return;

    let debounceTimer;
    let pendingController;

    const resolvePostalInput = () => {
      if (postalSelector) {
        const explicitPostalInput = document.querySelector(postalSelector);
        if (explicitPostalInput) {
          return explicitPostalInput;
        }
      }

      const form = input.closest('form');
      if (!form) {
        return null;
      }

      return form.querySelector(
        '[id$="postalCode"], [id$="postal_code"], [name$="[postalCode]"], [name$="[postal_code]"]'
      );
    };

    const clearSuggestions = () => {
      suggestionsContainer.innerHTML = '';
    };

    input.addEventListener('input', () => {
      const query = input.value.trim();

      window.clearTimeout(debounceTimer);
      if (pendingController) {
        pendingController.abort();
      }

      if (query.length < 1) {
        clearSuggestions();
        return;
      }

      debounceTimer = window.setTimeout(async () => {
        pendingController = new AbortController();

        try {
          const response = await fetch(`/api/address/search?q=${encodeURIComponent(query)}`, {
            signal: pendingController.signal
          });

          if (!response.ok) {
            clearSuggestions();
            return;
          }

          const data = await response.json();
          const results = Array.isArray(data.results) ? data.results : [];

          clearSuggestions();
          if (results.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'text-sm text-gray-500 px-2';
            empty.textContent = 'Aucune suggestion';
            suggestionsContainer.appendChild(empty);
            return;
          }

          const defaultPostalInput = resolvePostalInput();
          if (
            defaultPostalInput &&
            (!defaultPostalInput.value || defaultPostalInput.value.trim() === '') &&
            typeof results[0].postcode === 'string' &&
            results[0].postcode !== ''
          ) {
            defaultPostalInput.value = results[0].postcode;
          }

          results.forEach((result) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'block w-full text-left px-3 py-2 rounded-md bg-gray-50 hover:bg-gray-100 text-sm text-gray-700';
            button.textContent = result.label;
            button.addEventListener('mousedown', (event) => {
              event.preventDefault();
              input.value = result.city || result.label || '';
              const postalInput = resolvePostalInput();
              if (postalInput && typeof result.postcode === 'string' && result.postcode !== '') {
                postalInput.value = result.postcode;
              }
              clearSuggestions();
            });
            suggestionsContainer.appendChild(button);
          });
        } catch (error) {
          if (error.name !== 'AbortError') {
            clearSuggestions();
          }
        }
      }, 300);
    });
  });
}

function getSessionId() {
  const key = 'assistia_chat_session';
  let sessionId = window.sessionStorage.getItem(key);
  if (!sessionId) {
    sessionId = window.crypto?.randomUUID?.() ?? String(Date.now());
    window.sessionStorage.setItem(key, sessionId);
  }
  return sessionId;
}

function Chat({ embedded = false }) {
  const [messages, setMessages] = useState([
    { role: 'assistant', content: "Bonjour ! Indiquez votre budget ou le type de bien recherché (ex : T2, studio…)" }
  ]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [sessionId, setSessionId] = useState(getSessionId);
  const listRef = useRef(null);

  useEffect(() => {
    if (listRef.current) {
      listRef.current.scrollTop = listRef.current.scrollHeight;
    }
  }, [messages]);

  async function sendMessage() {
    const text = input.trim();
    if (!text || loading) return;

    setLoading(true);
    setMessages(prev => [...prev, { role: 'user', content: text }]);
    setInput('');

    try {
      const response = await fetch('/api/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text, sessionId }),
      });

      const data = await response.json();
      if (data.sessionId) {
        setSessionId(data.sessionId);
        window.sessionStorage.setItem('assistia_chat_session', data.sessionId);
      }
      setMessages(prev => [...prev, { role: 'assistant', content: data.reply ?? "Je n'ai pas compris" }]);
    } catch (e) {
      setMessages(prev => [...prev, { role: 'assistant', content: "Oups, erreur réseau. Réessaie." }]);
    } finally {
      setLoading(false);
    }
  }

  function onKeyDown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  }

  const width = embedded ? '100%' : 320;
  const messagesHeight = embedded ? 280 : 220;

  return React.createElement(
    'div',
    {
      style: {
        width,
        borderRadius: 16,
        background: 'white',
        boxShadow: embedded ? 'none' : '0 10px 30px rgba(0,0,0,0.15)',
        overflow: 'hidden',
        fontFamily: 'system-ui',
        border: embedded ? '1px solid #f3f4f6' : 'none',
      }
    },
    React.createElement(
      'div',
      { style: { padding: 12, borderBottom: '1px solid #eee', fontWeight: 700 } },
      embedded ? "Parlez à l'assistant Assist'IA" : "Assist'IA"
    ),
    React.createElement(
      'div',
      {
        ref: listRef,
        style: { height: messagesHeight, padding: 12, overflowY: 'auto', background: '#fafafa' }
      },
      messages.map((m, i) =>
        React.createElement(
          'div',
          {
            key: i,
            style: {
              display: 'flex',
              justifyContent: m.role === 'user' ? 'flex-end' : 'flex-start',
              marginBottom: 8,
            }
          },
          React.createElement(
            'div',
            {
              style: {
                maxWidth: '85%',
                padding: '8px 10px',
                borderRadius: 12,
                background: m.role === 'user' ? '#f472b6' : 'white',
                color: m.role === 'user' ? 'white' : '#111',
                border: m.role === 'user' ? 'none' : '1px solid #eee',
                whiteSpace: 'pre-wrap',
              }
            },
            m.content
          )
        )
      )
    ),
    React.createElement(
      'div',
      { style: { padding: 10, display: 'flex', gap: 8, borderTop: '1px solid #fbcfe8' } },
      React.createElement('textarea', {
        value: input,
        onChange: e => setInput(e.target.value),
        onKeyDown,
        placeholder: 'Ex : budget 150000 ou je cherche un T2…',
        rows: 1,
        style: {
          flex: 1,
          resize: 'none',
          borderRadius: 10,
          border: '1px solid #ddd',
          padding: '8px 10px',
          outline: 'none',
        }
      }),
      React.createElement(
        'button',
        {
          onClick: sendMessage,
          disabled: loading,
          style: {
            padding: '8px 12px',
            borderRadius: 10,
            border: 0,
            cursor: 'pointer',
            fontWeight: 700,
            background: loading ? '#f9a8d4' : '#ec4899',
            color: 'white',
          }
        },
        loading ? '...' : 'Envoyer'
      )
    )
  );
}

function bootstrapApp() {
  document.querySelectorAll('#react-chat').forEach((container) => {
    if (container.dataset.reactMounted === '1') {
      return;
    }
    container.dataset.reactMounted = '1';
    const embedded = container.dataset.chatEmbedded === '1';
    createRoot(container).render(React.createElement(Chat, { embedded }));
  });

  initAddressAutocomplete();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootstrapApp, { once: true });
} else {
  bootstrapApp();
}

document.addEventListener('turbo:load', bootstrapApp);
