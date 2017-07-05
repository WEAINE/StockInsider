import java.io.File;
import org.apache.lucene.store.Directory;
import org.apache.lucene.store.FSDirectory;
import org.apache.lucene.index.DirectoryReader;
import org.apache.lucene.index.Term;
import org.apache.lucene.document.Document;
import org.apache.lucene.search.IndexSearcher;
import org.apache.lucene.search.Query;
import org.apache.lucene.search.TermQuery;
import org.apache.lucene.search.WildcardQuery;
import org.apache.lucene.search.FuzzyQuery;
import org.apache.lucene.search.RegexpQuery;
import org.apache.lucene.search.ScoreDoc;

public class CompaniesAnnouncementsSearcher {
	private enum queryType { TERM, WILDCARD, FUZZY, REGEXP }

	private String field;
	private queryType type;
	private String queryString;

	public static void main(String[] args) throws Exception {
		CompaniesAnnouncementsSearcher companiesAnnouncementsSearcher = new CompaniesAnnouncementsSearcher();
		if (!companiesAnnouncementsSearcher.parseArgs(args)) {
			System.exit(-1);
			return;
		}

		File indexDir = new File("/home/ssd/StockInsider/index/announcements_companies");
		Directory directory = FSDirectory.open(indexDir.toPath());
		DirectoryReader directoryReader;
		try { directoryReader = DirectoryReader.open(directory); }
		catch (Exception e) {
			System.exit(-2);
			return;
		}

		IndexSearcher indexSearcher = new IndexSearcher(directoryReader);
		Term term = new Term(companiesAnnouncementsSearcher.field, companiesAnnouncementsSearcher.queryString);

		Query query;
		switch (companiesAnnouncementsSearcher.type) {
			case TERM:
				query = new TermQuery(term);
				break;
			case WILDCARD:
				query = new WildcardQuery(term);
				break;
			case FUZZY:
				query = new FuzzyQuery(term);
				break;
			case REGEXP:
				query = new RegexpQuery(term);
				break;
			default:
				directoryReader.close();
				directory.close();
				System.exit(-3);
				return;
		}

		ScoreDoc[] hits = indexSearcher.search(query, 100).scoreDocs;
		while (hits.length != 0) {
			for (ScoreDoc scoreDoc : hits) {
				Document hitDoc = indexSearcher.doc(scoreDoc.doc);
				System.out.println(hitDoc.get("path"));
			}

			hits = indexSearcher.searchAfter(hits[hits.length - 1], query, 100).scoreDocs;
		}

		directoryReader.close();
		directory.close();
	}

	private boolean parseArgs(String[] args) {
		if (args.length != 3) {
			showTips();
			return false;
		}

		if (args[0].equals("-path")) this.field = "path";
		else if (args[0].equals("-date")) this.field = "date";
		else if (args[0].equals("-title")) this.field = "title";
		else if (args[0].equals("-content")) this.field = "content";
		else {
			showTips();
			return false;
		}

		if (args[1].equals("--term")) this.type = queryType.TERM;
		else if (args[1].equals("--wildcard")) this.type = queryType.WILDCARD;
		else if (args[1].equals("--fuzzy")) this.type = queryType.FUZZY;
		else if (args[1].equals("--regexp")) this.type = queryType.REGEXP;
		else {
			showTips();
			return false;
		}

		this.queryString = args[2];
		return true;
	}

	private void showTips() {
		System.out.println("用法：java CompaniesAnnouncementsSearcher -field --type \"query string\"");
		System.out.println("搜索域field：当前支持参数path、date、title、content");
		System.out.println("搜索方式type：当前支持term（严格关键词搜索）、wildcard（通配符搜索，包括通配符\"?\"和通配符\"*\"）、fuzzy（模糊系数0.5的模糊搜索，以\"~\"修饰）、regexp（正则表达式搜索）");
	}
}
