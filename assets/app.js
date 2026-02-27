import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';

function Chat() {
  const [messages, setMessages] = useState([
    { role: 'assistant', content: "Bonjour 🙂 Dis-moi ce que tu cherches (ville, budget, chambres…)" }
  ]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
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
        body: JSON.stringify({ message: text }),
      });

      const data = await response.json();
      setMessages(prev => [...prev, { role: 'assistant', content: data.reply ?? "Je n’ai pas compris 😅" }]);
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

  return React.createElement(
    'div',
    {
      style: {
        width: 320,
        borderRadius: 16,
        background: 'white',
        boxShadow: '0 10px 30px rgba(0,0,0,0.15)',
        overflow: 'hidden',
        fontFamily: 'system-ui',
      }
    },
    // header
    React.createElement(
      'div',
      { style: { padding: 12, borderBottom: '1px solid #eee', fontWeight: 700 } },
      "Assist'IA 🤖"
    ),

    // messages
    React.createElement(
      'div',
      {
        ref: listRef,
        style: { height: 220, padding: 12, overflowY: 'auto', background: '#fafafa' }
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
                background: m.role === 'user' ? '#4f46e5' : 'white',
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

    // input
    React.createElement(
      'div',
      { style: { padding: 10, display: 'flex', gap: 8, borderTop: '1px solid #eee' } },
      React.createElement('textarea', {
        value: input,
        onChange: e => setInput(e.target.value),
        onKeyDown,
        placeholder: 'Écris ici…',
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
            background: loading ? '#9ca3af' : '#111827',
            color: 'white',
          }
        },
        loading ? '...' : 'Envoyer'
      )
    )
  );
}

const container = document.getElementById('react-chat');
if (container) {
  createRoot(container).render(React.createElement(Chat));
}
