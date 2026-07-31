import { Link } from 'react-router-dom';

export default function NotFoundPage() {
  return (
    <div className="empty">
      <h2>Page not found</h2>
      <p>The page you&apos;re looking for doesn&apos;t exist yet in the React app.</p>
      <p>
        <Link to="/">Return to the dashboard</Link>
      </p>
    </div>
  );
}
